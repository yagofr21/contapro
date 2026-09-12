<?php

namespace Tests\Feature\Domain;

use App\Modules\Finance\Models\Bank;
use App\Modules\Finance\Queries\BankOptionsQuery;
use Database\Seeders\BankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_seeder_is_idempotent(): void
    {
        $this->seed(BankSeeder::class);
        $count = Bank::count();
        $this->assertGreaterThan(8, $count);

        $this->seed(BankSeeder::class);

        $this->assertDatabaseCount('banks', $count);
    }

    public function test_bank_seeder_covers_popular_institutions(): void
    {
        $this->seed(BankSeeder::class);

        foreach (['nubank', 'itau', 'xp', 'mercado_pago', 'picpay', 'btg'] as $code) {
            $this->assertDatabaseHas('banks', ['code' => $code]);
        }
    }

    public function test_bank_options_query_exposes_display_metadata(): void
    {
        $this->seed(BankSeeder::class);

        $options = (new BankOptionsQuery)->active();

        $this->assertTrue($options->contains('value', 'mercado_pago'));
        $this->assertTrue($options->contains(fn (array $bank): bool => $bank['value'] === 'mercado_pago' && $bank['initials'] === 'MP'));
    }
}
