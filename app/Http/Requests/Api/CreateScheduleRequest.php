<?php

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
        $minDays = app()->environment('local', 'testing') ? 1 : 30;

        return [
            // Info Dasar
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
            'departure_date' => ['required', 'date', 'after_or_equal:' . now()->addDays($minDays)->toDateString()],
            'departure_time' => ['required', 'date_format:H:i'],
            'estimated_arrival' => ['nullable', 'date'],
            'travel_class' => ['required', 'in:economy,premium,charter'],
            'max_overload' => ['nullable', 'integer', 'min:0', 'max:2'],
            'price_per_seat' => ['required', 'numeric', 'min:1000'],
            'baggage_limit_kg' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'allow_cod' => ['nullable', 'boolean'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', 'in:midtrans,cash,cod'],

            // Stops
            'stops' => ['nullable', 'array'],
            'stops.*.route_stop_id' => ['required', 'integer', 'exists:route_stops,id'],
            'stops.*.is_pickup_available' => ['nullable', 'boolean'],
            'stops.*.is_dropoff_available' => ['nullable', 'boolean'],
            'stops.*.estimated_time' => ['nullable', 'date_format:H:i'],

            // Stop Config (JSON)
            'stop_config' => ['nullable', 'json'],

            // Pricing
            'pricing' => ['required', 'array', 'min:1'],
            'pricing.*.origin_stop_id' => ['required', 'integer', 'exists:route_stops,id'],
            'pricing.*.destination_stop_id' => ['required', 'integer', 'exists:route_stops,id', 'different:pricing.*.origin_stop_id'],
            'pricing.*.price' => ['required', 'numeric', 'min:1000'],

            // PP (Pulang-Pergi)
            'is_pp' => ['nullable', 'boolean'],
            'pp_date' => ['required_if:is_pp,true', 'nullable', 'date'],
            'pp_time' => ['required_if:is_pp,true', 'nullable', 'date_format:H:i'],
            'pp_rest_hours' => ['nullable', 'integer', 'min:0', 'max:48'],
            'pp_price' => ['nullable', 'numeric', 'min:1000'],
            'pp_stop_config' => ['nullable', 'json'],
            'pp_pricing' => ['nullable', 'json'],

            // Ketersediaan Rental
            'rest_days_before_rental' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_date.after_or_equal' => 'Jadwal harus H-' . (app()->environment('local', 'testing') ? 1 : 30) . ' sebelum keberangkatan.',
            'pricing.required' => 'Harga untuk setiap kombinasi stop wajib diisi.',
            'pricing.*.price.required' => 'Harga wajib diisi untuk setiap kombinasi.',
            'pricing.*.price.min' => 'Harga minimal Rp 1.000.',
            'pp_date.required_if' => 'Tanggal PP harus diisi jika PP diaktifkan.',
            'pp_time.required_if' => 'Jam PP harus diisi jika PP diaktifkan.',
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'max_overload' => $this->travel_class === 'economy' ? ($this->max_overload ?? 2) : 0,
        ]);
    }
}