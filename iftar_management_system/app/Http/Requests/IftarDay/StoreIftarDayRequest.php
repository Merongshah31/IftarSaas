<?php

namespace App\Http\Requests\IftarDay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\TenantContext;

class StoreIftarDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tarikh' => [
                'required', 
                'date', 
                'after_or_equal:today',
                // Unique date for this masjid
                Rule::unique('iftar_days')->where(function ($query) {
                    return $query->where('masjid_id', TenantContext::getTenantId());
                }),
            ],
            'kapasiti_max' => ['required', 'integer', 'min:10', 'max:1000'],
            'status' => ['nullable', Rule::in(['open', 'full', 'closed'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tarikh.after_or_equal' => 'Tarikh mesti hari ini atau akan datang',
            'tarikh.unique' => 'Tarikh ini sudah wujud untuk masjid anda',
            'kapasiti_max.min' => 'Kapasiti minimum adalah 10 orang',
            'kapasiti_max.max' => 'Kapasiti maksimum adalah 1000 orang',
        ];
    }
}
