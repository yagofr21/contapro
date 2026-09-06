<?php

namespace App\Modules\Investment\Http\Requests;

use App\Modules\Investment\Models\Broker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrokerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $broker = $this->route('broker');

        return $broker instanceof Broker
            ? $this->user()?->can('update', $broker) === true
            : $this->user()?->can('create', Broker::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $broker = $this->route('broker');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brokers', 'name')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at')
                    ->ignore($broker instanceof Broker ? $broker->id : null),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
