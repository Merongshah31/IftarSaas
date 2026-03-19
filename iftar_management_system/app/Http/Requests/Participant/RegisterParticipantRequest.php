<?php


namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'iftar_day_id' => ['required', 'integer', 'exists:iftar_days,id'],
            'nama' => ['required', 'string', 'max:255'],
            'no_telefon' => ['required', 'string', 'max:15', 'regex:/^01[0-9]-[0-9]{7,8}$/'],
            'bil_pax' => ['required', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'no_telefon.regex' => 'Nombor telefon mesti dalam format: 01X-XXXXXXX',
            'bil_pax.min' => 'Bilangan pax minimum adalah 1',
            'bil_pax.max' => 'Bilangan pax maksimum adalah 10',
        ];
    }
}