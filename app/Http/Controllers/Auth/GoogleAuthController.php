<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if ($user === null) {
            $user = User::query()->where('email', $googleUser->getEmail())->first();

            if ($user !== null) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            }
        }

        if ($user === null) {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?? $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => Str::password(32),
                'email_verified' => $googleUser->user['email_verified'] ?? true,
                'email_verified_at' => ($googleUser->user['email_verified'] ?? true) ? now() : null,
                'active_role' => 'client',
            ]);

            $user->assignRole('client');

            ClientProfile::query()->create([
                'user_id' => $user->id,
                'display_name' => $user->name,
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            session([
                'login.id' => $user->id,
                'login.remember' => true,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, true);
        request()->session()->regenerate();
        request()->session()->put('active_role', $user->resolveActiveRole());

        return redirect()->intended(route('dashboard'));
    }
}
