<?php
// File: app/Http/Requests/Api/UpdateAgencyRequest.php
// Deskripsi: Validasi request untuk update profil agency

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'agency';
    }

    public function rules(): array
    {
        return [
            'agency_name' => ['sometimes', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'founded_year' => ['nullable', 'integer', 'min:1950', 'max:' . date('Y')],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_alternate' => ['nullable', 'string', 'max:20'],
            'email_alternate' => ['nullable', 'email'],
            'services' => ['nullable', 'array'],
            'social_media' => ['nullable', 'array'],
            'business_hours' => ['nullable', 'array'],
            'zone_coverage' => ['nullable', 'array'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'data' => $validator->errors(),
            'meta' => null,
        ], 422));
    }
}

// End of file