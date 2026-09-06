<?php

namespace App\Modules\Finance\Http\Requests;

use App\Enums\Currency;
use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => ['required', Rule::enum(FinancialAccountType::class)],
            'currency' => ['required', Rule::enum(Currency::class)],
            'initial_balance' => ['required', 'decimal:0,4', 'between:-999999999999999.9999,999999999999999.9999'],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['initial_balance']);
    }
}
