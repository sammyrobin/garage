<?php

declare(strict_types=1);

namespace Garage\Core;

/**
 * Minimal router: exact paths and {param} placeholders.
 * Handlers are [ControllerClass::class, 'method'] and receive (Request, array $params).
 */
final class Router
{
    /** @var array<int, array{methods: string[], regex: string, handler: array}> */
    private array $routes = [];

    public function get(string $pattern, array $handler): self
    {
        return $this->add(['GET', 'HEAD'], $pattern, $handler);
    }

    public function post(string $pattern, array $handler): self
    {
        return $this->add(['POST'], $pattern, $handler);
    }

    public function any(string $pattern, array $handler): self
    {
        return $this->add(['*'], $pattern, $handler);
    }

    public function add(array $methods, string $pattern, array $handler): self
    {
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[a-z0-9-]+)', rtrim($pattern, '/') ?: '/');
        $this->routes[] = ['methods' => $methods, 'regex' => '#^' . $regex . '$#', 'handler' => $handler];

        return $this;
    }

    public function dispatch(string $method, string $path, Request $request): ?Response
    {
        $path = rtrim($path, '/') ?: '/';
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['methods'] !== ['*'] && !in_array($method, $route['methods'], true)) {
                continue;
            }

            [$class, $action] = $route['handler'];
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return (new $class())->$action($request, $params);
        }

        return $pathMatched ? Response::methodNotAllowed() : null;
    }
}
