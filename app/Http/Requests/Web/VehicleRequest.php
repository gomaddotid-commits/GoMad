<?php
// File: app/Http/Requests/Web/VehicleRequest.php
// Deskripsi: Validasi request web untuk CRUD kendaraan

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'agency';
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
        } else {
            $rules['plate_number'][] = 'unique:vehicles,plate_number,' . $this->route('vehicle');
        }

        return $rules;
    }
}

// End of file