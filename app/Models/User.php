<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'email_verified',
    'email_verified_at',
    'active_role',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
    'locked_until',
    'failed_login_attempts',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function vaProfile(): HasOne
    {
        return $this->hasOne(VaProfile::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function jobTemplates(): HasMany
    {
        return $this->hasMany(JobTemplate::class);
    }

    public function clientJobs(): HasMany
    {
        return $this->hasMany(MarketplaceJob::class, 'client_id');
    }

    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function serviceListings(): HasMany
    {
        return $this->hasMany(ServiceListing::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    public function isVa(): bool
    {
        return $this->hasRole('va');
    }

    public function isProvider(): bool
    {
        return $this->hasRole('provider');
    }

    public function isVaApproved(): bool
    {
        return $this->isVa()
            && $this->vaProfile !== null
            && $this->vaProfile->status === 'approved';
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified && $this->email_verified_at !== null;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null
            && $this->two_factor_confirmed_at !== null;
    }

    /**
     * @return list<string>
     */
    public function availableRoles(): array
    {
        $roles = [];

        if ($this->isClient()) {
            $roles[] = 'client';
        }

        if ($this->isVa()) {
            $roles[] = 'va';
        }

        if ($this->isProvider()) {
            $roles[] = 'provider';
        }

        return $roles;
    }

    public function resolveActiveRole(): ?string
    {
        $available = $this->availableRoles();

        if ($available === []) {
            return null;
        }

        if ($this->active_role !== null && in_array($this->active_role, $available, true)) {
            return $this->active_role;
        }

        return $available[0];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified' => true,
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }
}
