<?php

namespace App\Enums;

enum LedgerAccountType: string
{
    case ClientWallet = 'client_wallet';
    case VaWallet = 'va_wallet';
    case Escrow = 'escrow';
    case Platform = 'platform';

    public function requiresUser(): bool
    {
        return match ($this) {
            self::ClientWallet, self::VaWallet => true,
            self::Escrow, self::Platform => false,
        };
    }
}
