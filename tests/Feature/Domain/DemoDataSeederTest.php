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
        $this->assertDatabaseCount('financial_accounts', 4);
        $this->assertDatabaseCount('categories', 11);
        $this->assertDatabaseCount('transactions', 104);
        $this->assertDatabaseCount('budgets', 4);
        $this->assertDatabaseCount('expected_incomes', 3);
        $this->assertDatabaseCount('transaction_schedules', 4);
        $this->assertDatabaseCount('installments', 1);
        $this->assertDatabaseCount('financial_goals', 3);
        $this->assertDatabaseCount('assets', 4);
        $this->assertDatabaseCount('portfolios', 1);
        $this->assertDatabaseCount('portfolio_holdings', 4);
        $this->assertDatabaseCount('asset_transactions', 7);
        $this->assertDatabaseCount('price_history', 24);
    }
}
