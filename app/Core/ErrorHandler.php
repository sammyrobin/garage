<?php

declare(strict_types=1);

namespace Garage\Core;

use Throwable;

/** Converts errors to exceptions, logs them and shows a generic page in production. */
final class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug): void
    {
        self::$debug = $debug;
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handle(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        });
    }

    public static function handle(Throwable $e): void
    {
        Logger::error($e::class . ': ' . $e->getMessage(), [
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (self::$debug) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo $e::class . ': ' . $e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
            return;
        }

        try {
            Response::html(View::render('errors/500', ['title' => t('error.500.title')], 'layouts/public'), 500)->send();
        } catch (Throwable) {
            http_response_code(500);
            echo 'Something went wrong.';
        }
    }
}
