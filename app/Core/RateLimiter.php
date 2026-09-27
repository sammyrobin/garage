<?php

declare(strict_types=1);

namespace Garage\Core;

/**
 * Failed-password limiter per real IP (sliding window, stored in MySQL so it works
 * across PHP processes on shared hosting). IPs are stored as an HMAC, never raw.
 */
final class RateLimiter
{
    public function __construct(
        private readonly int $maxAttempts,
        private readonly int $windowSeconds,
    ) {
    }

    public static function forOwnerPassword(): self
    {
        return new self((int) Config::get('admin.max_attempts', 5), (int) Config::get('admin.attempt_window', 900));
    }

    /** Seconds until the IP may try again, or 0 if it is not locked. */
    public function lockedFor(string $ip): int
    {
        $row = Database::query(
            'SELECT COUNT(*) AS attempts, MIN(attempted_at) AS oldest
               FROM login_attempts
              WHERE ip_hash = ? AND attempted_at > NOW() - INTERVAL ? SECOND',
            [$this->hash($ip), $this->windowSeconds]
        )->fetch();

        if ((int) $row['attempts'] < $this->maxAttempts) {
            return 0;
        }

        return max(1, strtotime((string) $row['oldest']) + $this->windowSeconds - time());
    }

    public function hit(string $ip): void
    {
        Database::query('INSERT INTO login_attempts (ip_hash, attempted_at) VALUES (?, NOW())', [$this->hash($ip)]);

        // Housekeeping: old rows are useless.
        if (random_int(1, 20) === 1) {
            Database::query('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY');
        }
    }

    public function clear(string $ip): void
    {
        Database::query('DELETE FROM login_attempts WHERE ip_hash = ?', [$this->hash($ip)]);
    }

    private function hash(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) Config::get('app.key'));
    }
}
