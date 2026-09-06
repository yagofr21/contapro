<?php

namespace App\Modules\ImportExport\Http\Requests;

use App\Modules\ImportExport\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmCsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('importBatch');

        return $batch instanceof ImportBatch && $this->user()?->can('update', $batch) === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['skip_invalid' => ['required', 'boolean']];
    }
}
