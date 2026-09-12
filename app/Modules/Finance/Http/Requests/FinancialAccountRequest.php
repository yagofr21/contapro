<?php

namespace App\Modules\Finance\Http\Requests;

use App\Enums\Currency;
use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FinancialAccountRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $account = $this->route('account');

        return $account instanceof FinancialAccount
            ? $this->user()?->can('update', $account) === true
            : $this->user()?->can('create', FinancialAccount::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bank' => ['nullable', 'string', Rule::exists('banks', 'code')],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'type' => ['required', Rule::enum(FinancialAccountType::class)],
            'currency' => ['required', Rule::enum(Currency::class)],
            'initial_balance' => ['required', 'decimal:0,4', 'between:-999999999999999.9999,999999999999999.9999'],
            'credit_limit' => ['nullable', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999'],
            'credit_closing_day' => ['nullable', 'integer', 'between:1,28'],
            'credit_due_day' => ['nullable', 'integer', 'between:1,28'],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['initial_balance', 'credit_limit']);
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = $this->string('type')->toString();
            $hasCreditFields = $this->input('credit_limit') !== null
                || $this->input('credit_closing_day') !== null
                || $this->input('credit_due_day') !== null;

            if ($type !== FinancialAccountType::CreditCard->value && $hasCreditFields) {
                $validator->errors()->add('type', 'Campos de limite e fatura sao exclusivos de cartao de credito.');
            }
        }];
    }
}
