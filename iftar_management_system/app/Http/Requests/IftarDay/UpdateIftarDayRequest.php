<?php

namespace App\Http\Requests\IftarDay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\TenantContext;

class UpdateIftarDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tarikh' => [
                'sometimes',
                'date',
                'after_or_equal:today',
                Rule::unique('iftar_days')
                    ->where('masjid_id', TenantContext::getTenantId())
                    ->ignore($this->route('iftarDay')),
            ],
            'kapasiti_max' => ['sometimes', 'integer', 'min:10', 'max:1000'],
            'status' => ['sometimes', Rule::in(['open', 'full', 'closed'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}