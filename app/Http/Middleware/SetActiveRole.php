<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetActiveRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $sessionRole = $request->session()->get('active_role');
        $availableRoles = $user->availableRoles();

        if ($sessionRole !== null && in_array($sessionRole, $availableRoles, true)) {
            $activeRole = $sessionRole;
        } else {
            $activeRole = $user->resolveActiveRole();
        }

        if ($activeRole !== null) {
            $request->session()->put('active_role', $activeRole);

            if ($user->active_role !== $activeRole) {
                $user->forceFill(['active_role' => $activeRole])->save();
            }
        }

        View::share('activeRole', $activeRole);
        View::share('availableRoles', $availableRoles);

        return $next($request);
    }
}
