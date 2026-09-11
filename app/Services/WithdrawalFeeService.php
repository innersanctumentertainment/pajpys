<?php

namespace App\Services;

class WithdrawalFeeService
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
    ) {}

    /**
     * @return array{fee_minor: int, net_minor: int, fee_percentage: float}
     */
    public function calculateVaFee(int $amountMinor): array
    {
        return $this->calculate($amountMinor, (float) $this->settings->get('withdrawal_fee', 15));
    }

    /**
     * @return array{fee_minor: int, net_minor: int, fee_percentage: float}
     */
    public function calculateClientFee(int $amountMinor): array
    {
        return $this->calculate($amountMinor, (float) $this->settings->get('withdrawal_fee', 15));
    }

    public function vaMinimumMinor(): int
    {
        return (int) round(((float) $this->settings->get('va_withdrawal_minimum', 150)) * 100);
    }

    /**
     * @return array{fee_minor: int, net_minor: int, fee_percentage: float}
     */
    private function calculate(int $amountMinor, float $feePercentage): array
    {
        $feeMinor = (int) round($amountMinor * ($feePercentage / 100));

        return [
            'fee_minor' => $feeMinor,
            'net_minor' => $amountMinor - $feeMinor,
            'fee_percentage' => $feePercentage,
        ];
    }
}
