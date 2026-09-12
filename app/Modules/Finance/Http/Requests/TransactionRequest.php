<?php

namespace App\Modules\Finance\Http\Requests;

use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Enums\FinancialAccountType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransactionRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction instanceof Transaction
            ? $this->user()?->can('update', $transaction) === true
            : $this->user()?->can('create', Transaction::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $transaction = $this->route('transaction');
        $allowedTypes = match (true) {
            $transaction instanceof Transaction && $transaction->transfer_id !== null => ['transfer'],
            $transaction instanceof Transaction => ['income', 'expense'],
            default => ['income', 'expense', 'transfer'],
        };
        $accountRule = Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query
            ->where('user_id', $this->user()?->id)
            ->where('is_archived', false)
            ->whereNull('deleted_at'));

        return [
            'type' => ['required', Rule::in($allowedTypes)],
            'account_id' => ['required', 'integer', $accountRule],
            'destination_account_id' => [
                'nullable',
                'required_if:type,transfer',
                'integer',
                'different:account_id',
                $accountRule,
            ],
            'category_id' => [
                'nullable',
                'prohibited_if:type,transfer',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at')),
            ],
            'amount' => ['required', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999'],
            'transaction_date' => ['nullable', 'date_format:Y-m-d', 'required_unless:install_in,true'],
            'description' => ['nullable', 'string', 'max:2000'],
            'install_in' => ['sometimes', 'boolean'],
            'total_count' => ['nullable', 'required_if:install_in,true', 'integer', 'min:2', 'max:120'],
            'first_installment_date' => ['nullable', 'required_if:install_in,true', 'date_format:Y-m-d'],
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
            $categoryId = $this->integer('category_id');
            $type = $this->string('type')->toString();

            if ($categoryId !== 0 && $type !== 'transfer') {
                $category = Category::query()
                    ->whereBelongsTo($this->user())
                    ->find($categoryId);

                if ($category !== null && $category->type->value !== $type) {
                    $validator->errors()->add('category_id', 'A categoria deve corresponder ao tipo da transacao.');
                }
            }

            if ($this->boolean('install_in')) {
                if ($type !== 'expense') {
                    $validator->errors()->add('type', 'Somente despesas podem ser parceladas.');
                }

                $account = FinancialAccount::query()
                    ->whereBelongsTo($this->user())
                    ->find($this->integer('account_id'));

                if ($account !== null && $account->type !== FinancialAccountType::CreditCard) {
                    $validator->errors()->add('account_id', 'Parcele somente em contas de cartao de credito.');
                }
            }
        }];
    }
}
