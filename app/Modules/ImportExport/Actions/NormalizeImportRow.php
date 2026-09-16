<?php

namespace App\Modules\ImportExport\Actions;

use App\Models\User;
use App\Modules\ImportExport\Enums\ImportKind;
use App\Modules\ImportExport\Support\DecimalParser;
use App\Modules\ImportExport\Support\StrictDateParser;
use App\Modules\Investment\Models\Asset;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Support\Str;
use InvalidArgumentException;

class NormalizeImportRow
{
    public function __construct(
        private DecimalParser $decimalParser,
        private StrictDateParser $dateParser,
    ) {}

    /**
     * @param  array<string, string>  $raw
     * @return array{normalized: array<string, mixed>|null, errors: list<string>}
     */
    public function handle(User $user, ImportKind $kind, array $raw, ?Portfolio $portfolio): array
    {
        return $kind === ImportKind::Financial
            ? $this->financial($user, $raw)
            : $this->investment($user, $raw, $portfolio);
    }

    /** @param array<string, string> $raw
     * @return array{normalized: array<string, mixed>|null, errors: list<string>}
     */
    private function financial(User $user, array $raw): array
    {
        $errors = [];
        $type = match ($this->normalizedText($this->value($raw, 'Tipo'))) {
            'receita', 'income' => 'income',
            'despesa', 'expense' => 'expense',
            'transferencia', 'transfer' => 'transfer',
            default => null,
        };

        if ($type === null) {
            $errors[] = 'Tipo inválido. Use Receita, Despesa ou Transferência.';
        }

        $currency = strtoupper($this->value($raw, 'Moeda'));
        $account = $user->financialAccounts()
            ->where('is_archived', false)
            ->where('name', $this->value($raw, 'Conta'))
            ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
            ->get();

        if ($account->count() !== 1) {
            $errors[] = 'Conta não encontrada ou ambígua para esta moeda.';
        }

        $sourceAccount = $account->first();
        $destinationAccount = null;

        if ($type === 'transfer') {
            $destination = $user->financialAccounts()
                ->where('is_archived', false)
                ->where('name', $this->value($raw, 'Conta destino'))
                ->when($currency !== '', fn ($query) => $query->where('currency', $currency))
                ->get();

            if ($destination->count() !== 1 || $destination->first()?->is($sourceAccount)) {
                $errors[] = 'Conta de destino não encontrada, ambígua ou igual à origem.';
            }

            $destinationAccount = $destination->first();
        }

        $category = null;
        $categoryName = $this->value($raw, 'Categoria');

        if ($categoryName !== '' && $type !== 'transfer') {
            $categories = $user->categories()
                ->where('name', $categoryName)
                ->when($type !== null, fn ($query) => $query->where('type', $type))
                ->get();

            if ($categories->count() !== 1) {
                $errors[] = 'Categoria não encontrada ou ambígua para o tipo do lançamento.';
            }

            $category = $categories->first();
        }

        $amount = $this->decimal($this->value($raw, 'Valor'), 4, 'Valor', $errors);
        $date = $this->date($this->value($raw, 'Data'), $errors);

        if ($amount !== null && bccomp($amount, '0', 4) <= 0) {
            $errors[] = 'Valor deve ser maior que zero.';
        }

        if ($errors !== []) {
            return ['normalized' => null, 'errors' => $errors];
        }

        return ['normalized' => [
            'type' => $type,
            'account_id' => $sourceAccount?->id,
            'destination_account_id' => $destinationAccount?->id,
            'category_id' => $category?->id,
            'amount' => $amount,
            'transaction_date' => $date,
            'description' => $this->value($raw, 'Descrição') ?: null,
        ], 'errors' => []];
    }

    /** @param array<string, string> $raw
     * @return array{normalized: array<string, mixed>|null, errors: list<string>}
     */
    private function investment(User $user, array $raw, ?Portfolio $portfolio): array
    {
        $errors = [];

        if ($portfolio === null || $portfolio->user_id !== $user->id) {
            return ['normalized' => null, 'errors' => ['Carteira invalida para a importacao.']];
        }

        $type = match ($this->normalizedText($this->value($raw, 'Tipo'))) {
            'compra', 'buy' => 'buy',
            'venda', 'sell' => 'sell',
            'dividendo', 'dividend' => 'dividend',
            'juros', 'interest' => 'interest',
            'desdobramento', 'grupamento', 'split' => 'split',
            default => null,
        };

        if ($type === null) {
            $errors[] = 'Tipo de operação inválido.';
        }

        $asset = Asset::query()
            ->where('symbol', strtoupper($this->value($raw, 'Ativo')))
            ->where('market', strtoupper($this->value($raw, 'Mercado')))
            ->where('currency', $portfolio->currency->value)
            ->where('is_active', true)
            ->first();

        if ($asset === null) {
            $errors[] = 'Ativo não encontrado, inativo ou com moeda diferente da carteira.';
        }

        $broker = null;
        $brokerName = $this->value($raw, 'Corretora');

        if ($brokerName !== '') {
            $brokers = $user->brokers()->where('name', $brokerName)->get();

            if ($brokers->count() !== 1) {
                $errors[] = 'Corretora não encontrada ou ambígua.';
            }

            $broker = $brokers->first();
        }

        $date = $this->date($this->value($raw, 'Data'), $errors);
        $quantity = $type === 'buy' || $type === 'sell'
            ? $this->decimal($this->value($raw, 'Quantidade'), 8, 'Quantidade', $errors)
            : '0.00000000';
        $unitPrice = $type === 'buy' || $type === 'sell'
            ? $this->decimal($this->value($raw, 'Preço unitário'), 8, 'Preço unitário', $errors)
            : '0.00000000';
        $fees = $type === 'buy' || $type === 'sell'
            ? $this->decimal($this->value($raw, 'Taxas') ?: '0', 4, 'Taxas', $errors)
            : '0.0000';
        $grossAmount = $type === 'dividend' || $type === 'interest'
            ? $this->decimal($this->value($raw, 'Valor bruto'), 4, 'Valor bruto', $errors)
            : null;
        $netAmount = $type === 'dividend' || $type === 'interest'
            ? $this->decimal($this->value($raw, 'Valor líquido'), 4, 'Valor líquido', $errors)
            : null;
        $splitFrom = $type === 'split'
            ? $this->decimal($this->value($raw, 'Proporção origem'), 8, 'Proporção origem', $errors)
            : null;
        $splitTo = $type === 'split'
            ? $this->decimal($this->value($raw, 'Proporção destino'), 8, 'Proporção destino', $errors)
            : null;

        if (($type === 'buy' || $type === 'sell')
            && (($quantity !== null && bccomp($quantity, '0', 8) <= 0)
                || ($unitPrice !== null && bccomp($unitPrice, '0', 8) <= 0))) {
            $errors[] = 'Quantidade e preço unitário devem ser maiores que zero.';
        }

        if ($fees !== null && bccomp($fees, '0', 4) < 0) {
            $errors[] = 'Taxas não podem ser negativas.';
        }

        if (($type === 'dividend' || $type === 'interest')
            && ($grossAmount !== null && $netAmount !== null)
            && (bccomp($grossAmount, '0', 4) <= 0 || bccomp($netAmount, '0', 4) < 0 || bccomp($netAmount, $grossAmount, 4) > 0)) {
            $errors[] = 'Valores bruto e líquido do provento são inválidos.';
        }

        if ($type === 'split' && $splitFrom !== null && $splitTo !== null
            && (bccomp($splitFrom, '0', 8) <= 0 || bccomp($splitTo, '0', 8) <= 0 || bccomp($splitFrom, $splitTo, 8) === 0)) {
            $errors[] = 'A proporção deve usar valores positivos e diferentes.';
        }

        if ($errors !== []) {
            return ['normalized' => null, 'errors' => $errors];
        }

        return ['normalized' => [
            'asset_id' => $asset?->id,
            'broker_id' => $broker?->id,
            'type' => $type,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'fees' => $fees,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'split_from' => $splitFrom,
            'split_to' => $splitTo,
            'transaction_date' => $date,
            'note' => $this->value($raw, 'Observação') ?: null,
        ], 'errors' => []];
    }

    /** @param array<string, string> $raw */
    private function value(array $raw, string $header): string
    {
        $target = $this->normalizedText($header);

        foreach ($raw as $key => $value) {
            if ($this->normalizedText($key) === $target) {
                $value = trim($value);

                return preg_match('/^\'[=+\-@]/', $value) === 1 ? substr($value, 1) : $value;
            }
        }

        return '';
    }

    private function normalizedText(string $value): string
    {
        return mb_strtolower(Str::ascii(trim($value)));
    }

    /** @param list<string> $errors */
    private function decimal(string $value, int $scale, string $field, array &$errors): ?string
    {
        try {
            return $this->decimalParser->parse($value, $scale, $scale === 8 ? 12 : 15);
        } catch (InvalidArgumentException $exception) {
            $errors[] = "{$field}: {$exception->getMessage()}";

            return null;
        }
    }

    /** @param list<string> $errors */
    private function date(string $value, array &$errors): ?string
    {
        try {
            return $this->dateParser->parse($value);
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();

            return null;
        }
    }
}
