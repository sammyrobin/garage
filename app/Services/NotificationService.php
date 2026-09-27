<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Config;
use Garage\Core\Database;
use Garage\Core\Logger;
use Garage\Core\View;
use Garage\Models\Car;
use Garage\Models\CarPhoto;

/**
 * E-mail announcements for new cars, grouped into one summary.
 *
 * - "Save and add another" only queues the car.
 * - "Save" sends everything pending right away, in a single e-mail.
 * - Anything left pending (e.g. the tab was closed) is sent once no car has been
 *   added for 10 minutes, by the cPanel cron (bin/notify.php) or the next admin visit.
 *
 * A failed e-mail never blocks saving: it is logged and retried at most 3 times.
 */
final class NotificationService
{
    public const GROUP_WINDOW = 600;
    private const MAX_ATTEMPTS = 3;
    private const STALE_CLAIM = 300;

    public static function queue(int $carId): void
    {
        Database::query('INSERT INTO notification_queue (car_id) VALUES (?)', [$carId]);
    }

    /** Send pending cars if the group window closed (cron / lazy flush). */
    public static function flushDue(): bool
    {
        $last = Database::query(
            'SELECT MAX(queued_at) FROM notification_queue WHERE sent_at IS NULL AND attempts < ?',
            [self::MAX_ATTEMPTS]
        )->fetchColumn();

        if (!$last || Database::toTime((string) $last) > time() - self::GROUP_WINDOW) {
            return false;
        }

        return self::flush();
    }

    /** Claim every pending car atomically and send one summary. */
    public static function flush(): bool
    {
        $token = bin2hex(random_bytes(16));
        $claimed = Database::query(
            'UPDATE notification_queue
                SET claim_token = ?, claimed_at = ?
              WHERE sent_at IS NULL AND attempts < ?
                AND (claim_token IS NULL OR claimed_at < ?)',
            [$token, Database::utc(), self::MAX_ATTEMPTS, Database::utc(-self::STALE_CLAIM)]
        )->rowCount();

        if ($claimed === 0) {
            return false;
        }

        $carIds = array_map('intval', Database::query(
            'SELECT DISTINCT car_id FROM notification_queue WHERE claim_token = ?',
            [$token]
        )->fetchAll(\PDO::FETCH_COLUMN));

        $sent = false;
        try {
            $cars = Car::findMany($carIds);
            $sent = $cars !== [] && self::send($cars);
        } catch (\Throwable $e) {
            Logger::error('New-car e-mail failed: ' . $e->getMessage());
        }

        if ($sent) {
            Database::query('UPDATE notification_queue SET sent_at = ? WHERE claim_token = ?', [Database::utc(), $token]);
        } else {
            Database::query(
                'UPDATE notification_queue SET attempts = attempts + 1, claim_token = NULL WHERE claim_token = ?',
                [$token]
            );
            Logger::warning('New-car e-mail not sent; will retry', ['cars' => count($carIds)]);
        }

        return $sent;
    }

    /** Build and send the summary. Public for the preview script. */
    public static function send(array $cars): bool
    {
        $message = self::compose($cars);

        return Mailer::fromConfig()->send(
            (string) Config::get('mail.notify_to', 'samuelt@conqr.mx'),
            $message['subject'],
            $message['html'],
            $message['text'],
        );
    }

    /** @return array{subject: string, html: string, text: string} */
    public static function compose(array $cars): array
    {
        $photos = CarPhoto::forCars(array_map(static fn (array $c): int => (int) $c['id'], $cars), ['front']);
        foreach ($cars as &$car) {
            $front = $photos[(int) $car['id']]['front'] ?? null;
            $car['thumb_url'] = $front ? PhotoStorage::absoluteUrl($front['file_key'], 'em') : null;
            $car['detail_url'] = absolute_url('/auto/' . $car['slug'], 'es');
        }
        unset($car);

        $quota = DiskStatus::quota();
        $vars = [
            'cars' => $cars,
            'totals' => Car::totals(),
            'quota' => $quota,
            'usage' => DiskStatus::garageUsage(),
            'adminUrl' => absolute_url('/admin', 'es'),
            'siteUrl' => absolute_url('/', 'es'),
        ];

        return [
            'subject' => self::subject($cars, $quota),
            'html' => View::render('emails/new-cars', $vars),
            'text' => View::render('emails/new-cars-text', $vars),
        ];
    }

    public static function subject(array $cars, ?array $quota): string
    {
        $percent = $quota['percent'] ?? null;
        $disk = 'Disco ' . ($percent === null ? 'n/d' : rtrim(rtrim(number_format($percent, 1), '0'), '.') . '%');
        $icon = $percent !== null && $percent > 85 ? '⚠️' : '🏎️';

        if (count($cars) === 1) {
            return "{$icon} Nuevo en el Garage: {$cars[0]['name']} — {$disk}";
        }

        $names = array_column(array_slice($cars, 0, 2), 'name');
        $rest = count($cars) - count($names);
        $list = implode(', ', $names) . ($rest > 0 ? " y {$rest} más" : '');

        return "{$icon} " . count($cars) . " nuevos en el Garage: {$list} — {$disk}";
    }
}
