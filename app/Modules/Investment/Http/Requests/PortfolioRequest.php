<?php

namespace App\Modules\Investment\Http\Requests;

use App\Enums\Currency;
use App\Modules\Investment\Models\Portfolio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $portfolio = $this->route('portfolio');

        return $portfolio instanceof Portfolio
            ? $this->user()?->can('update', $portfolio) === true
            : $this->user()?->can('create', Portfolio::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::enum(Currency::class)],
        ];
    }
}
