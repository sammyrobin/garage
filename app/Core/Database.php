<?php

declare(strict_types=1);

namespace Garage\Core;

use PDO;

/**
 * Lazy shared PDO connection to the SQLite file. Prepared statements only — never
 * concatenate SQL.
 *
 * The database is a single file in storage/ (blocked from the web, ignored by git,
 * so a deploy never uploads or deletes it). Timestamps are stored in UTC, like
 * SQLite's CURRENT_TIMESTAMP: compare them with Database::utc(), parse them with
 * Database::toTime().
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function path(): string
    {
        return (string) (Config::get('db.path') ?: GARAGE_ROOT . '/storage/garage.sqlite');
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            if (!extension_loaded('pdo_sqlite')) {
                throw new \RuntimeException('The pdo_sqlite PHP extension is not enabled.');
            }
            self::$pdo = new PDO('sqlite:' . self::path(), null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            // Foreign keys are off by default in SQLite; cascades depend on them.
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }

        return self::$pdo;
    }

    /** Run a prepared statement and return it. */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /** Current UTC time (plus an offset in seconds) in the format SQLite stores. */
    public static function utc(int $offsetSeconds = 0): string
    {
        return gmdate('Y-m-d H:i:s', time() + $offsetSeconds);
    }

    /** Unix time of a stored UTC timestamp. */
    public static function toTime(string $stored): int
    {
        return (int) strtotime($stored . ' UTC');
    }
}
