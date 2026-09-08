<?php

namespace App\Modules\ImportExport\Http\Requests;

use App\Modules\ImportExport\Models\Reconciliation;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reconciliation = $this->route('reconciliation');

        return $reconciliation instanceof Reconciliation && $this->user()?->can('update', $reconciliation) === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [];
    }
}
