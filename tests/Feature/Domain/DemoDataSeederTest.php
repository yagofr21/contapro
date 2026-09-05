<?php

namespace Tests\Feature\Domain;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('financial_accounts', 1);
        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('budgets', 1);
        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseCount('portfolios', 1);
        $this->assertDatabaseCount('portfolio_holdings', 1);
        $this->assertDatabaseCount('asset_transactions', 2);
        $this->assertDatabaseCount('price_history', 1);
    }
}
