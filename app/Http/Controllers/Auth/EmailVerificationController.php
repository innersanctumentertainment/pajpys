<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect('/');
        }

        return view('auth.verify-email');
    }

    public function verifySigned(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Email already verified.');
        }

        $this->authService->verifyEmail($user);
        event(new Verified($user));

        return redirect()->route('login')->with('status', 'Email verified. You may now log in.');
    }
}
