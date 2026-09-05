<?php

namespace App\Modules\Investment\Http\Requests;

use App\Enums\Currency;
use App\Modules\Investment\Enums\AssetType;
use App\Modules\Investment\Enums\Market;
use App\Modules\Investment\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Asset::class) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'symbol' => strtoupper(trim($this->string('symbol')->toString())),
            'name' => trim($this->string('name')->toString()),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9.\-]+$/',
                Rule::unique('assets')->where('market', $this->string('market')->toString()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AssetType::class)],
            'market' => ['required', Rule::enum(Market::class)],
            'currency' => ['required', Rule::enum(Currency::class)],
        ];
    }
}
