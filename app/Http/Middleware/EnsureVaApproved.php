<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVaApproved
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->isVa()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Provider role required.'], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'You need a provider account to access this area.');
        }

        if (! $user->isVaApproved()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your provider profile must be approved before accessing job features.',
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Your provider profile is pending approval.');
        }

        return $next($request);
    }
}
