<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Services\CurrencyService;
use App\Services\PlatformSettingsService;
use Tests\MarketplaceTestCase;

class CurrencyServiceTest extends MarketplaceTestCase
{
    private CurrencyService $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currency = app(CurrencyService::class);
    }

    public function test_convert_between_currencies_using_stored_rate(): void
    {
        $this->currency->storeRate(Currency::USD, Currency::TTD, '6.80');
        $this->assertSame(68000, $this->currency->convert(10000, Currency::USD, Currency::TTD));
    }

    public function test_convert_returns_same_amount_for_identical_currency(): void
    {
        $this->assertSame(5000, $this->currency->convert(5000, Currency::TTD, Currency::TTD));
    }

    public function test_display_for_va_floors_ttd_conversion(): void
    {
        $this->currency->storeRate(Currency::USD, Currency::TTD, '6.789');
        $this->assertSame(67890, $this->currency->displayForVa(10000, Currency::USD));
    }

    public function test_display_for_va_returns_original_amount_in_ttd(): void
    {
        $this->assertSame(15000, $this->currency->displayForVa(15000, Currency::TTD));
    }

    public function test_convert_uses_platform_settings_fallback(): void
    {
        app(PlatformSettingsService::class)->set('currency_rates.GBP_TTD', '8.50', 'decimal', 'currency');
        $this->assertSame(17000, $this->currency->convert(2000, Currency::GBP, Currency::TTD));
    }
}
