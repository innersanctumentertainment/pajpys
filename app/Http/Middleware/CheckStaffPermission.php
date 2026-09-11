<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStaffPermission
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->hasAnyRole(['master_admin', 'staff'])) {
            abort(403, 'Staff access required.');
        }

        foreach ($permissions as $permission) {
            if (! $user->can($permission)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'You do not have permission to perform this action.',
                    ], 403);
                }

                abort(403, 'You do not have permission to perform this action.');
            }
        }

        return $next($request);
    }
}
