<?php

declare(strict_types=1);

namespace Garage\Core;

use PDO;

/**
 * Applies migrations/NNN_name.sql in order, once each, tracked in schema_migrations.
 * Files are written to be idempotent too (IF NOT EXISTS / INSERT IGNORE), so a
 * partially applied file can safely be re-run.
 */
final class Migrator
{
    public function __construct(private readonly PDO $pdo, private readonly string $dir)
    {
    }

    /** @return array{applied: string[], skipped: int} */
    public function run(): array
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(191) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $done = $this->pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob($this->dir . '/[0-9][0-9][0-9]_*.sql') ?: [];
        sort($files, SORT_STRING);

        $applied = [];
        foreach ($files as $file) {
            $version = basename($file, '.sql');
            if (in_array($version, $done, true)) {
                continue;
            }

            foreach (self::statements((string) file_get_contents($file)) as $sql) {
                $this->pdo->exec($sql);
            }

            $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$version]);
            $applied[] = $version;
            Logger::info('Migration applied', ['version' => $version]);
        }

        return ['applied' => $applied, 'skipped' => count($files) - count($applied)];
    }

    /** Split a SQL file on semicolons that end a line, ignoring "-- " comment lines. */
    public static function statements(string $sql): array
    {
        $lines = array_filter(
            preg_split('/\R/', $sql) ?: [],
            static fn (string $line): bool => !str_starts_with(ltrim($line), '--')
        );
        $parts = preg_split('/;\s*$/m', implode("\n", $lines)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $s): bool => $s !== ''));
    }
}
