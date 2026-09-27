<?php

declare(strict_types=1);

namespace Garage\Core;

/** Immutable view of the current HTTP request, with the base path already stripped. */
final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $files,
        private readonly array $server,
    ) {
    }

    public static function fromGlobals(string $basePath): self
    {
        $uri = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            '/' . ltrim($uri, '/'),
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
        );
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return isset($this->server[$key]) ? (string) $this->server[$key] : null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function ip(): string
    {
        return ClientIp::resolve($this->server);
    }

    /** True when the visitor reached us over HTTPS (Cloudflare Flexible talks HTTP to the origin). */
    public function isSecure(): bool
    {
        return ClientIp::isSecure($this->server);
    }
}
