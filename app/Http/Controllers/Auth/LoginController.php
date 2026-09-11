<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $result = $this->authService->attemptLogin(
            $request->validated('email'),
            $request->validated('password'),
            (bool) $request->validated('remember'),
        );

        return match ($result['status']) {
            'success' => tap(
                redirect()->intended('/'),
                fn () => $request->session()->regenerate(),
            ),
            'locked', 'unverified' => back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $result['message']]),
            default => back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
