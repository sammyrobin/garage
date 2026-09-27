<?php

declare(strict_types=1);

namespace Garage\Core;

/**
 * Work that runs after the response has been sent (e.g. SMTP), so the browser
 * is not kept waiting. Uses fastcgi/litespeed_finish_request when available;
 * otherwise the work simply runs at the end of the request.
 */
final class Deferred
{
    /** @var callable[] */
    private static array $tasks = [];

    public static function add(callable $task): void
    {
        self::$tasks[] = $task;
    }

    public static function run(): void
    {
        if (self::$tasks === []) {
            return;
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        ignore_user_abort(true);
        @set_time_limit(60);

        foreach (self::$tasks as $task) {
            try {
                $task();
            } catch (\Throwable $e) {
                Logger::error('Deferred task failed: ' . $e->getMessage(), ['file' => $e->getFile() . ':' . $e->getLine()]);
            }
        }
        self::$tasks = [];
    }
}
