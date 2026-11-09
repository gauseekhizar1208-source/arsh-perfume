<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side admin gate. The decision is made from the authenticated user's role in the database,
 * never from anything the browser sends (URL, form field, header or JavaScript).
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->isAdmin()) {
            abort(403, 'You do not have access to the administration area.');
        }

        return $next($request);
    }
}
