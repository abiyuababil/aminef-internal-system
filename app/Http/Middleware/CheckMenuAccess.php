<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccess
{
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        $user = $request->user();
        abort_unless($user && $user->hasMenuAccess($menuKey), 403, 'You do not have permission to access this menu.');

        return $next($request);
    }
}
