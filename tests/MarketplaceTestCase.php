<?php

namespace Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class MarketplaceTestCase extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        app(\App\Services\PlatformSettingsService::class)->flushCache();
    }
}
