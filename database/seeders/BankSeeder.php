<?php

namespace Database\Seeders;

use App\Modules\Finance\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Semeia um catalogo base de bancos e instituicoes financeiras usados
     * na identificacao visual das contas. Edicoes podem ser feitas
     * diretamente na tabela 'banks' em producao.
     */
    public function run(): void
    {
        foreach ($this->catalog() as $row) {
            Bank::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'label' => $row['label'],
                    'color' => $row['color'],
                    'initials' => $row['initials'],
                    'is_active' => true,
                ],
            );
        }
    }

    /** @return list<array{code: string, label: string, color: string, initials: string}> */
    private function catalog(): array
    {
        return [
            ['code' => 'nubank', 'label' => 'Nubank', 'color' => '#820AD1', 'initials' => 'N'],
            ['code' => 'itau', 'label' => 'Itau', 'color' => '#EC7001', 'initials' => 'IT'],
            ['code' => 'bradesco', 'label' => 'Bradesco', 'color' => '#CC092F', 'initials' => 'BR'],
            ['code' => 'santander', 'label' => 'Santander', 'color' => '#EC0000', 'initials' => 'ST'],
            ['code' => 'banco_do_brasil', 'label' => 'Banco do Brasil', 'color' => '#153B86', 'initials' => 'BB'],
            ['code' => 'caixa', 'label' => 'Caixa', 'color' => '#0059A9', 'initials' => 'CX'],
            ['code' => 'inter', 'label' => 'Inter', 'color' => '#FF7A00', 'initials' => 'IN'],
            ['code' => 'c6', 'label' => 'C6 Bank', 'color' => '#1B1B1B', 'initials' => 'C6'],
            ['code' => 'xp', 'label' => 'XP Investimentos', 'color' => '#0058A0', 'initials' => 'XP'],
            ['code' => 'mercado_pago', 'label' => 'Mercado Pago', 'color' => '#00A7E0', 'initials' => 'MP'],
            ['code' => 'picpay', 'label' => 'PicPay', 'color' => '#11C76F', 'initials' => 'PP'],
            ['code' => 'btg', 'label' => 'BTG Pactual', 'color' => '#00512E', 'initials' => 'BT'],
        ];
    }
}
