<?php

declare(strict_types=1);

namespace Garage\Core;

/** Plain PHP templates in app/Views. Templates must escape output with e(). */
final class View
{
    private static array $shared = [];

    /** Data available to every template (e.g. the current path for the language switch). */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::capture($template, $data);

        return $layout === null ? $content : self::capture($layout, $data + ['content' => $content]);
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        $file = GARAGE_APP . '/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($data + self::$shared, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
