<?php

namespace App\Modules\Finance\Http\Requests;

use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Enums\ScheduleFrequency;
use App\Modules\Finance\Models\TransactionSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionScheduleRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $schedule = $this->route('recurring');

        return $schedule instanceof TransactionSchedule
            ? $this->user()?->can('update', $schedule) === true
            : $this->user()?->can('create', TransactionSchedule::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $allowedTypes = match ($this->input('type')) {
            'transfer' => ['transfer'],
            default => ['income', 'expense'],
        };
        $accountRule = Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query
            ->where('user_id', $this->user()?->id)
            ->where('is_archived', false)
            ->whereNull('deleted_at'));

        return [
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
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
            'frequency' => ['required', Rule::enum(ScheduleFrequency::class)],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['amount']);
    }
}
