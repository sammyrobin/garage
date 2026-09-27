<?php

declare(strict_types=1);

namespace Garage\Core;

/** Resolves the language from the URL, dispatches the route and sends the response. */
final class App
{
    public function __construct(private readonly Router $router)
    {
    }

    public function run(): void
    {
        $request = Request::fromGlobals((string) Config::get('app.base_path', ''));

        [$lang, $path] = Lang::split($request->path);
        Lang::set($lang);
        View::share('currentPath', $path);

        $response = $this->router->dispatch($request->method, $path, $request)
            ?? Response::notFound();

        $response->send();
    }
}
