<?php

declare(strict_types=1);

namespace Garage\Core;

/** HTTP response with the app's security headers applied on send. */
final class Response
{
    private const CSP = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: blob:; "
        . "font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; "
        . "frame-ancestors 'none'";

    private array $headers = [];

    public function __construct(private string $body = '', private int $status = 200)
    {
    }

    public static function html(string $body, int $status = 200): self
    {
        return (new self($body, $status))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(array $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return (new self($body, $status))
            ->header('Content-Type', 'application/json; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return (new self('', $status))->header('Location', $location);
    }

    public static function notFound(): self
    {
        return self::html(View::render('errors/404', ['title' => t('error.404.title')], 'layouts/public'), 404);
    }

    public static function methodNotAllowed(): self
    {
        return self::html(View::render('errors/404', ['title' => t('error.404.title')], 'layouts/public'), 405);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /** Keep a page out of search engines (admin panel). */
    public function noIndex(): self
    {
        return $this->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=(self)');
            header('Content-Security-Policy: ' . self::CSP);
            header_remove('X-Powered-By');
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $this->body;
    }
}
