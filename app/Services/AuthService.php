<?php

namespace App\Services;

use App\Models\ClientProfile;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Models\VaProfile;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, role: string}  $data
     */
    public function register(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified' => false,
            'email_verified_at' => null,
            'active_role' => $this->resolveInitialRole($data['role']),
        ]);

        $this->assignRoles($user, $data['role']);
        $this->createProfiles($user, $data['role']);

        $user->notify(new VerifyEmailNotification);

        $this->auditLog->log(
            event: 'auth.registered',
            auditable: $user,
            newValues: ['email' => $user->email, 'role' => $data['role']],
            user: $user,
        );

        return $user;
    }

    /**
     * @return array{status: string, user?: User, message?: string}
     */
    public function attemptLogin(string $email, string $password, bool $remember = false): array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return ['status' => 'invalid_credentials'];
        }

        if ($user->isLocked()) {
            return [
                'status' => 'locked',
                'message' => 'Account is temporarily locked. Please try again later.',
            ];
        }

        if (! Hash::check($password, $user->password)) {
            $this->recordFailedAttempt($user);

            return ['status' => 'invalid_credentials'];
        }

        if (! $user->hasVerifiedEmail()) {
            return [
                'status' => 'unverified',
                'message' => 'Please verify your email address before logging in.',
            ];
        }

        $this->clearFailedAttempts($user);
        Auth::login($user, $remember);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $this->auditLog->log(
            event: 'auth.login',
            auditable: $user,
            user: $user,
        );

        return ['status' => 'success', 'user' => $user->fresh()];
    }

    public function verifyEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->forceFill([
            'email_verified' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->auditLog->log(
            event: 'auth.email_verified',
            auditable: $user,
            user: $user,
        );
    }

    private function recordFailedAttempt(User $user): void
    {
        $maxAttempts = $this->settings->getInt('max_failed_login_attempts', 5);
        $lockoutMinutes = $this->settings->getInt('account_lockout_minutes', 30);

        $attempts = $user->failed_login_attempts + 1;
        $updates = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $updates['locked_until'] = now()->addMinutes($lockoutMinutes);

            $this->auditLog->log(
                event: 'auth.account_locked',
                auditable: $user,
                newValues: ['failed_login_attempts' => $attempts],
            );
        }

        $user->update($updates);
    }

    private function clearFailedAttempts(User $user): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    private function resolveInitialRole(string $role): ?string
    {
        return match ($role) {
            'client' => 'client',
            'provider' => 'provider',
            'va' => 'va',
            'both' => 'client',
            'client_provider' => 'client',
            default => throw new RuntimeException('Invalid registration role.'),
        };
    }

    private function assignRoles(User $user, string $role): void
    {
        match ($role) {
            'client' => $user->assignRole('client'),
            'provider' => $user->assignRole('provider'),
            'va' => $user->assignRole('va'),
            'both' => $user->assignRole(['client', 'va']),
            'client_provider' => $user->assignRole(['client', 'provider']),
            default => throw new RuntimeException('Invalid registration role.'),
        };
    }

    private function createProfiles(User $user, string $role): void
    {
        if (in_array($role, ['client', 'both'], true)) {
            ClientProfile::query()->create([
                'user_id' => $user->id,
                'display_name' => $user->name,
                'status' => 'active',
            ]);
        }

        if (in_array($role, ['va', 'both'], true)) {
            VaProfile::query()->create([
                'user_id' => $user->id,
                'display_name' => $user->name,
                'status' => 'pending',
                'availability_status' => 'available',
            ]);
        }

        if (in_array($role, ['provider', 'client_provider'], true)) {
            ProviderProfile::query()->create([
                'user_id' => $user->id,
                'display_name' => $user->name,
                'status' => 'active',
            ]);
        }
    }
}
