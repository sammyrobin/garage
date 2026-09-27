<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Config;
use Garage\Core\Logger;
use Garage\Support\Cache;

/**
 * Hosting account usage (cPanel UAPI Quota) and space used by the Garage photos.
 *
 * disk_free_space() is deliberately NOT used: on shared hosting it reports the
 * whole server's disk, not this account's quota.
 */
final class DiskStatus
{
    private const QUOTA_TTL = 600;        // 10 min
    private const QUOTA_FAIL_TTL = 120;   // retry a failing API after 2 min, not on every page
    private const USAGE_TTL = 3600;       // uploads/ size: recalculated at most hourly

    /** @return array{used: int, limit: int, percent: ?float, level: string}|null null when cPanel could not be read */
    public static function quota(): ?array
    {
        $cached = Cache::remember('cpanel-quota', self::QUOTA_TTL, static fn (): array => self::fetchQuota());
        if (!($cached['ok'] ?? false)) {
            if (($cached['at'] ?? 0) < time() - self::QUOTA_FAIL_TTL) {
                Cache::forget('cpanel-quota');
            }
            return null;
        }

        $percent = $cached['limit'] > 0 ? round($cached['used'] / $cached['limit'] * 100, 1) : null;

        return [
            'used' => (int) $cached['used'],
            'limit' => (int) $cached['limit'],
            'percent' => $percent,
            'level' => self::level($percent),
        ];
    }

    /** @return array{bytes: int, photos: int, computed_at: int} */
    public static function garageUsage(): array
    {
        return Cache::remember('garage-usage', self::USAGE_TTL, static function (): array {
            $bytes = 0;
            $photos = 0;
            $dir = PhotoStorage::dir();
            if (is_dir($dir)) {
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    $bytes += $file->getSize();
                    if (str_ends_with($file->getFilename(), '-lg.webp')) {
                        $photos++;
                    }
                }
            }

            return ['bytes' => $bytes, 'photos' => $photos, 'computed_at' => time()];
        });
    }

    public static function level(?float $percent): string
    {
        return match (true) {
            $percent === null => 'unknown',
            $percent > 85 => 'red',
            $percent >= 70 => 'yellow',
            default => 'green',
        };
    }

    public static function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) (int) $bytes : number_format($bytes, $bytes < 10 ? 2 : 1)) . ' ' . $units[$i];
    }

    /** GET https://HOST:2083/execute/Quota/get_quota_info with an API token. */
    private static function fetchQuota(): array
    {
        $host = (string) Config::get('cpanel.host', '');
        $user = (string) Config::get('cpanel.user', '');
        $token = (string) Config::get('cpanel.token', '');
        $fail = ['ok' => false, 'at' => time()];

        if ($host === '' || $user === '' || $token === '') {
            return $fail;
        }

        $url = 'https://' . $host . ':2083/execute/Quota/get_quota_info';
        $auth = 'Authorization: cpanel ' . $user . ':' . $token;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [$auth],
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 6,
                // Calling the same server by IP/localhost does not match its certificate.
                CURLOPT_SSL_VERIFYPEER => (bool) Config::get('cpanel.verify_tls', true),
                CURLOPT_SSL_VERIFYHOST => Config::get('cpanel.verify_tls', true) ? 2 : 0,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
        } else {
            $ctx = stream_context_create(['http' => ['header' => $auth, 'timeout' => 6, 'ignore_errors' => true]]);
            $body = @file_get_contents($url, false, $ctx);
            $status = $body === false ? 0 : 200;
            $error = $body === false ? 'request failed' : '';
        }

        $json = is_string($body) ? json_decode($body, true) : null;
        $data = is_array($json) ? ($json['data'] ?? null) : null;
        if ($status !== 200 || !is_array($data) || (int) ($json['status'] ?? 0) !== 1) {
            // Never log the token or the Authorization header.
            Logger::warning('cPanel quota unavailable', ['http' => $status, 'error' => $error ?: ($json['errors'][0] ?? 'unexpected response')]);
            return $fail;
        }

        $used = isset($data['bytes_used']) ? (float) $data['bytes_used'] : (float) ($data['megabytes_used'] ?? 0) * 1048576;
        $limit = isset($data['byte_limit']) ? (float) $data['byte_limit'] : (float) ($data['megabyte_limit'] ?? 0) * 1048576;

        return ['ok' => true, 'used' => (int) $used, 'limit' => (int) $limit, 'at' => time()];
    }
}
