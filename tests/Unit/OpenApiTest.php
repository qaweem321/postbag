<?php

use Illuminate\Support\Facades\Route;

// Keeps docs/openapi.yaml honest: every API route must be documented, and nothing documented may be missing.
it('documents every API route', function () {
    $spec = file_get_contents(base_path('docs/openapi.yaml'));
    preg_match_all('/^  (\/\S+):$/m', $spec, $matches);
    $documented = collect($matches[1])->sort()->values()->all();

    $routes = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->filter(fn (string $uri) => str_starts_with($uri, 'api/v1/'))
        ->map(fn (string $uri) => '/'.substr($uri, strlen('api/v1/')))
        ->unique()->sort()->values()->all();

    expect($documented)->toBe($routes);
});
