<?php

namespace App\Modules\Investment\Http\Requests;

use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Investment\Enums\AssetTransactionType;
use App\Modules\Investment\Models\AssetTransaction;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetTransactionRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $transaction = $this->route('investment_transaction');
        $portfolio = $this->route('portfolio');

        if ($transaction instanceof AssetTransaction) {
            return $this->user()?->can('update', $transaction) === true;
        }

        return $portfolio instanceof Portfolio
            && $this->user()?->can('view', $portfolio) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $portfolio = $this->route('portfolio');

        if (! $portfolio instanceof Portfolio) {
            $transaction = $this->route('investment_transaction');
            $portfolio = $transaction instanceof AssetTransaction ? $transaction->portfolio : null;
        }

        $isTrade = in_array($this->input('type'), [
            AssetTransactionType::Buy->value,
            AssetTransactionType::Sell->value,
        ], true);

        $isIncome = in_array($this->input('type'), [
            AssetTransactionType::Dividend->value,
            AssetTransactionType::Interest->value,
        ], true);
        $isSplit = $this->input('type') === AssetTransactionType::Split->value;

        return [
            'asset_id' => [
                'required',
                'integer',
                Rule::exists('assets', 'id')
                    ->where('currency', $portfolio?->currency->value)
                    ->whereNull('deleted_at'),
            ],
            'broker_id' => [
                'nullable',
                'integer',
                Rule::exists('brokers', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::in([
                AssetTransactionType::Buy->value,
                AssetTransactionType::Dividend->value,
                AssetTransactionType::Interest->value,
                AssetTransactionType::Sell->value,
                AssetTransactionType::Split->value,
            ])],
            'quantity' => $isTrade
                ? ['required', 'decimal:0,8', 'gt:0', 'max:999999999999.99999999']
                : ['required', 'decimal:0,8', 'in:0'],
            'unit_price' => $isTrade
                ? ['required', 'decimal:0,8', 'gt:0', 'max:999999999999.99999999']
                : ['required', 'decimal:0,8', 'in:0'],
            'fees' => ['required', 'decimal:0,4', 'gte:0', 'max:999999999999999.9999'],
            'split_from' => $isSplit
                ? ['required', 'decimal:0,8', 'gt:0', 'max:999999999999.99999999', 'different:split_to']
                : ['nullable'],
            'split_to' => $isSplit
                ? ['required', 'decimal:0,8', 'gt:0', 'max:999999999999.99999999', 'different:split_from']
                : ['nullable'],
            'gross_amount' => $isIncome
                ? ['required', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999']
                : ['nullable'],
            'net_amount' => $isIncome
                ? ['required', 'decimal:0,4', 'gte:0', 'lte:gross_amount', 'max:999999999999999.9999']
                : ['nullable'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.exists' => 'O ativo deve existir e usar a mesma moeda da carteira.',
            'net_amount.lte' => 'O valor liquido nao pode ser maior que o valor bruto.',
            'broker_id.exists' => 'A corretora deve pertencer ao seu cadastro.',
            'split_from.different' => 'A proporcao deve alterar a quantidade do ativo.',
            'split_to.different' => 'A proporcao deve alterar a quantidade do ativo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput([
            'quantity',
            'unit_price',
            'fees',
            'gross_amount',
            'net_amount',
            'split_from',
            'split_to',
        ]);

        $isIncome = in_array($this->input('type'), [
            AssetTransactionType::Dividend->value,
            AssetTransactionType::Interest->value,
        ], true);
        $isSplit = $this->input('type') === AssetTransactionType::Split->value;

        $this->merge(match (true) {
            $isIncome => [
                'quantity' => '0',
                'unit_price' => '0',
                'fees' => '0',
                'split_from' => null,
                'split_to' => null,
            ],
            $isSplit => [
                'quantity' => '0',
                'unit_price' => '0',
                'fees' => '0',
                'gross_amount' => null,
                'net_amount' => null,
            ],
            default => [
                'gross_amount' => null,
                'net_amount' => null,
                'split_from' => null,
                'split_to' => null,
            ],
        });
    }
}
