<?php
// File: app/Http/Requests/Api/CreateScheduleRequest.php
// Deskripsi: Validasi request untuk membuat jadwal

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class CreateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'agency';
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
            'departure_date' => ['required', 'date', 'after_or_equal:' . now()->addDays(30)->toDateString()],
            'departure_time' => ['required', 'date_format:H:i'],
            'travel_class' => ['required', 'in:economy,premium,charter,rental'],
            'max_overload' => ['nullable', 'integer', 'min:0', 'max:2'],
            'price_per_seat' => ['required', 'numeric', 'min:1000'],
            'baggage_limit_kg' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'stops' => ['nullable', 'array'],
            'stops.*.route_stop_id' => ['required', 'integer', 'exists:route_stops,id'],
            'stops.*.is_pickup_available' => ['nullable', 'boolean'],
            'stops.*.is_dropoff_available' => ['nullable', 'boolean'],
            'stops.*.estimated_time' => ['nullable', 'date_format:H:i'],
            'pricing' => ['required', 'array', 'min:1'],
            'pricing.*.origin_stop_id' => ['required', 'integer', 'exists:route_stops,id'],
            'pricing.*.destination_stop_id' => ['required', 'integer', 'exists:route_stops,id', 'different:pricing.*.origin_stop_id'],
            'pricing.*.price' => ['required', 'numeric', 'min:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_date.after_or_equal' => 'Jadwal harus dibuat minimal H-30 sebelum keberangkatan.',
            'pricing.required' => 'Harga untuk setiap kombinasi stop wajib diisi.',
            'pricing.*.price.required' => 'Harga wajib diisi untuk setiap kombinasi.',
            'pricing.*.price.min' => 'Harga minimal Rp 1.000.',
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