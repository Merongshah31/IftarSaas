<?php

namespace App\Http\Requests\Sponsorship;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSponsorshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'iftar_day_id' => ['nullable', 'integer', 'exists:iftar_days,id'],
            'nama_sponsor' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:15'],
            'jumlah_tajaan' => ['required', 'numeric', 'min:10', 'max:100000'],
            'jenis_tajaan' => ['required', Rule::in(['IFTAR', 'MOREH'])],
            'sponsor_coverage' => ['required', Rule::in(['FULL', 'PARTIAL', 'GENERAL'])],
            'payment_status' => ['nullable', Rule::in(['PENDING', 'PAID', 'FAILED'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'jumlah_tajaan.min' => 'Jumlah tajaan minimum RM10',
            'jumlah_tajaan.max' => 'Jumlah tajaan maksimum RM100,000',
        ];
    }
}
