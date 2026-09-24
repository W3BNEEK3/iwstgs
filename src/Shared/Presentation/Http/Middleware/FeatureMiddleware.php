<?php

namespace Src\Shared\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind a feature flag. A disabled feature 404s rather than
 * 403s — it should look like the route doesn't exist, not like access was
 * denied, since the whole point is deploying dead code safely.
 */
class FeatureMiddleware
{
    public function __construct(private readonly FeatureFlagService $flags) {}

    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless($this->flags->isEnabled($key), 404);

        return $next($request);
    }
}
