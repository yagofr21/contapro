<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\Bank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bank = $this->route('bank');

        return $bank instanceof Bank
            ? $this->user()?->can('update', $bank) === true
            : $this->user()?->can('create', Bank::class) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $bank = $this->route('bank');

        return [
            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('banks', 'code')->ignore($bank instanceof Bank ? $bank->id : null),
            ],
            'label' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'initials' => ['required', 'string', 'max:4'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower((string) $this->input('code', '')),
            'label' => trim((string) $this->input('label', '')),
            'initials' => strtoupper(trim((string) $this->input('initials', ''))),
        ]);
    }
}
