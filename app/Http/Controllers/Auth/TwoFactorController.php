<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorSetupRequest;
use App\Http\Requests\Auth\TwoFactorVerifyRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA as Google2FAService;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly Google2FAService $google2fa,
    ) {}

    public function showSetupForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.recovery');
        }

        if ($user->two_factor_secret === null) {
            $secret = $this->google2fa->generateSecretKey();
            $user->forceFill(['two_factor_secret' => encrypt($secret)])->save();
        }

        $secret = decrypt($user->fresh()->two_factor_secret);
        $authenticator = app(Authenticator::class);
        $qrCodeInline = $authenticator->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret,
        );

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'qrCodeInline' => $qrCodeInline,
        ]);
    }

    public function confirmSetup(TwoFactorSetupRequest $request): RedirectResponse
    {
        $user = $request->user();
        $secret = decrypt($user->two_factor_secret);

        if (! $this->google2fa->verifyKey($secret, $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $recoveryCodes = Collection::times(8, fn () => Str::upper(Str::random(10)))->all();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        $request->session()->put('two_factor_recovery_codes', $recoveryCodes);

        return redirect()->route('two-factor.recovery')
            ->with('status', 'Two-factor authentication enabled.');
    }

    public function showRecoveryCodes(Request $request): View
    {
        $codes = $request->session()->pull('two_factor_recovery_codes');

        if ($codes === null && $request->user()->two_factor_recovery_codes !== null) {
            $codes = json_decode(decrypt($request->user()->two_factor_recovery_codes), true);
        }

        return view('auth.two-factor-recovery', [
            'recoveryCodes' => $codes ?? [],
        ]);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Two-factor authentication disabled.');
    }

    public function challenge(): View|RedirectResponse
    {
        if (! session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(TwoFactorVerifyRequest $request): RedirectResponse
    {
        $userId = $request->session()->get('login.id');

        if ($userId === null) {
            return redirect()->route('login');
        }

        $user = User::query()->findOrFail($userId);

        if ($this->verifyChallenge($user, $request)) {
            $remember = (bool) $request->session()->pull('login.remember', false);
            $request->session()->forget('login.id');

            Auth::login($user, $remember);
            $request->session()->regenerate();
            $request->session()->put('active_role', $user->resolveActiveRole());
            $request->session()->put(config('google2fa.session_var'), true);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['code' => 'Invalid authentication or recovery code.']);
    }

    private function verifyChallenge(User $user, TwoFactorVerifyRequest $request): bool
    {
        if ($request->filled('recovery_code')) {
            return $this->consumeRecoveryCode($user, $request->string('recovery_code')->toString());
        }

        if ($user->two_factor_secret === null) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        return $this->google2fa->verifyKey(
            $secret,
            $request->string('code')->toString(),
        );
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        if ($user->two_factor_recovery_codes === null) {
            return false;
        }

        /** @var list<string> $codes */
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $normalized = Str::upper(trim($code));
        $index = array_search($normalized, array_map(fn (string $stored) => Str::upper($stored), $codes), true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
        ])->save();

        return true;
    }
}
