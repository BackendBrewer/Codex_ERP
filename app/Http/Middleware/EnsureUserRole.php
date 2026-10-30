<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, $roles, true), 403);

        if ($user->role === 'admin') {
            abort_unless($user->academy !== null && $user->academy->is_active, 403, 'This academy account is inactive. Contact the system administrator.');
        }

        return $next($request);
    }
}
