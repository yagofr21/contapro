<?php

namespace App\Modules\Finance\Http\Requests;

use App\Enums\Currency;
use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\ExpectedIncome;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExpectedIncomeRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $expectedIncome = $this->route('expectedIncome');

        return $expectedIncome instanceof ExpectedIncome
            ? $this->user()?->can('update', $expectedIncome) === true
            : $this->user()?->can('create', ExpectedIncome::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $accountRule = Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query
            ->where('user_id', $this->user()?->id)
            ->where('is_archived', false)
            ->whereNull('deleted_at'));

        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'account_id' => ['required', 'integer', $accountRule],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at')),
            ],
            'expected_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['amount']);
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $account = FinancialAccount::query()
                ->whereBelongsTo($this->user())
                ->withTrashed()
                ->find($this->integer('account_id'));

            if ($account !== null && $account->currency->value !== $this->string('currency')->toString()) {
                $validator->errors()->add('currency', 'A moeda deve corresponder ao saldo da conta escolhida.');
            }

            $category = $this->integer('category_id') !== 0
                ? Category::query()
                    ->whereBelongsTo($this->user())
                    ->find($this->integer('category_id'))
                : null;

            if ($category !== null && $category->type->value !== 'income') {
                $validator->errors()->add('category_id', 'A categoria deve ser do tipo receita.');
            }
        }];
    }
}
