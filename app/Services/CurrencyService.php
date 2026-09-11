<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\CurrencyRate;
use InvalidArgumentException;

class CurrencyService
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
    ) {}

    public function convert(
        int $amountMinor,
        Currency|string $from,
        Currency|string $to,
    ): int {
        $from = $this->normalizeCurrency($from);
        $to = $this->normalizeCurrency($to);

        if ($from === $to) {
            return $amountMinor;
        }

        return (int) round($amountMinor * $this->getRate($from, $to));
    }

    /**
     * Display amount for VAs in TTD, always rounded down (floor).
     */
    public function displayForVa(int $amountMinor, Currency|string $sourceCurrency): int
    {
        $sourceCurrency = $this->normalizeCurrency($sourceCurrency);

        if ($sourceCurrency === Currency::TTD) {
            return $amountMinor;
        }

        return (int) floor($amountMinor * $this->getRate($sourceCurrency, Currency::TTD));
    }

    public function formatMinor(int $amountMinor, Currency|string $currency): string
    {
        $currency = $this->normalizeCurrency($currency);
        $major = $amountMinor / $currency->minorUnitFactor();

        return sprintf('%s %.2f', $currency->value, $major);
    }

    public function storeRate(
        Currency|string $from,
        Currency|string $to,
        string|float $rate,
        ?\DateTimeInterface $effectiveAt = null,
    ): CurrencyRate {
        return CurrencyRate::query()->create([
            'base_currency' => $this->normalizeCurrency($from)->value,
            'quote_currency' => $this->normalizeCurrency($to)->value,
            'rate' => $rate,
            'effective_at' => $effectiveAt ?? now(),
        ]);
    }

    public function getRate(Currency|string $from, Currency|string $to): float
    {
        $from = $this->normalizeCurrency($from);
        $to = $this->normalizeCurrency($to);

        if ($from === $to) {
            return 1.0;
        }

        $direct = $this->latestStoredRate($from->value, $to->value);

        if ($direct !== null) {
            return (float) $direct;
        }

        $inverse = $this->latestStoredRate($to->value, $from->value);

        if ($inverse !== null && (float) $inverse > 0) {
            return 1 / (float) $inverse;
        }

        $viaTtd = $this->crossRateVia($from, $to, Currency::TTD);

        if ($viaTtd !== null) {
            return $viaTtd;
        }

        $configured = $this->settings->get("currency_rates.{$from->value}_{$to->value}");

        if ($configured !== null) {
            return (float) $configured;
        }

        throw new InvalidArgumentException("No exchange rate available for {$from->value} to {$to->value}.");
    }

    private function latestStoredRate(string $base, string $quote): ?string
    {
        return CurrencyRate::query()
            ->where('base_currency', $base)
            ->where('quote_currency', $quote)
            ->where('effective_at', '<=', now())
            ->orderByDesc('effective_at')
            ->value('rate');
    }

    private function crossRateVia(Currency $from, Currency $to, Currency $via): ?float
    {
        try {
            return $this->getDirectOrInverseRate($from, $via) * $this->getDirectOrInverseRate($via, $to);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function getDirectOrInverseRate(Currency $from, Currency $to): float
    {
        $direct = $this->latestStoredRate($from->value, $to->value);

        if ($direct !== null) {
            return (float) $direct;
        }

        $inverse = $this->latestStoredRate($to->value, $from->value);

        if ($inverse !== null && (float) $inverse > 0) {
            return 1 / (float) $inverse;
        }

        throw new InvalidArgumentException("Missing rate for {$from->value} to {$to->value}.");
    }

    private function normalizeCurrency(Currency|string $currency): Currency
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        return Currency::from(strtoupper($currency));
    }
}
