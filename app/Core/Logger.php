<?php

declare(strict_types=1);

namespace Garage\Core;

/** Append-only monthly log files in storage/logs (blocked from the web). */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''
        );

        $dir = GARAGE_ROOT . '/storage/logs';
        if (!is_dir($dir) || @file_put_contents($dir . '/app-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX) === false) {
            error_log('GARAGE ' . trim($line));
        }
    }
}
