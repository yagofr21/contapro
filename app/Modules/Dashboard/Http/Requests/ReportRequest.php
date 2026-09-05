<?php

namespace App\Modules\Dashboard\Http\Requests;

use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'currency' => ['required', Rule::enum(Currency::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $today = now($this->user()->timezone ?? config('app.timezone'));

        $this->merge([
            'from' => $this->input('from', $today->copy()->subMonths(11)->startOfMonth()->toDateString()),
            'to' => $this->input('to', $today->toDateString()),
            'currency' => $this->input('currency', Currency::BRL->value),
        ]);
    }
}
