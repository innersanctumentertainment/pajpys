<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitAuth
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action = 'login'): Response
    {
        $limits = [
            'login' => ['max' => 10, 'decay' => 60],
            'register' => ['max' => 5, 'decay' => 60],
            'password' => ['max' => 5, 'decay' => 60],
            'two_factor' => ['max' => 10, 'decay' => 60],
        ];

        $config = $limits[$action] ?? $limits['login'];
        $key = sprintf('auth:%s:%s', $action, sha1($request->ip().'|'.$request->input('email', '')));

        if (RateLimiter::tooManyAttempts($key, $config['max'])) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too many attempts. Please try again in '.$seconds.' seconds.',
                ], 429);
            }

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Too many attempts. Please try again in '.$seconds.' seconds.']);
        }

        RateLimiter::hit($key, $config['decay']);

        $response = $next($request);

        if ($request->hasSession() && ($request->session()->has('status') || auth()->check())) {
            RateLimiter::clear($key);
        }

        return $response;
    }
}
