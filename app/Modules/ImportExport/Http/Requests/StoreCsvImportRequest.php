<?php

namespace App\Modules\ImportExport\Http\Requests;

use App\Modules\ImportExport\Enums\ImportKind;
use App\Modules\ImportExport\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ImportBatch::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(ImportKind::class)],
            'portfolio_id' => [
                'nullable',
                'required_if:kind,'.ImportKind::Investment->value,
                'integer',
                Rule::exists('portfolios', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at'),
            ],
            'file' => ['required', 'file', 'max:2048', 'extensions:csv,txt'],
        ];
    }
}
