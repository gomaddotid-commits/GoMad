<?php
// File: app/Http/Requests/Api/VehicleRequest.php
// Deskripsi: Validasi request untuk CRUD kendaraan

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'agency';
    }

    public function rules(): array
    {
        $rules = [
            'plate_number' => ['required', 'string', 'max:20'],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'capacity' => ['required', 'integer', 'min:4', 'max:20'],
            'type' => ['required', 'in:economy,premium'],
        ];

        if ($this->isMethod('post')) {
            $rules['plate_number'][] = 'unique:vehicles,plate_number';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $vehicleId = $this->route('vehicle');
            $rules['plate_number'][] = 'unique:vehicles,plate_number,' . $vehicleId;
        }

        return $rules;
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