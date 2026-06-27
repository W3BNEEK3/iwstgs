<?php

namespace Src\Identity\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $request->user()->hasRole($role)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}