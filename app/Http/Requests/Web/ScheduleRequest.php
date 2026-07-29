<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'agency';
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
            'pricing' => ['required', 'array', 'min:1'],
            'pricing.*.origin_stop_id' => ['required', 'integer'],
            'pricing.*.destination_stop_id' => ['required', 'integer'],
            'pricing.*.price' => ['required', 'numeric', 'min:1000'],
            'allow_cod' => ['nullable', 'boolean'],

            // Stops Config
            'stop_config' => ['nullable', 'json'],
            'stops' => ['nullable', 'array'],
            'stops.*.route_stop_id' => ['required', 'integer', 'exists:route_stops,id'],
            'stops.*.is_pickup_available' => ['nullable', 'boolean'],
            'stops.*.is_dropoff_available' => ['nullable', 'boolean'],
            'stops.*.estimated_time' => ['nullable', 'date_format:H:i'],

            // PP (Pulang-Pergi)
            'is_pp' => ['nullable', 'boolean'],
            'pp_date' => ['required_if:is_pp,1', 'nullable', 'date'],
            'pp_time' => ['required_if:is_pp,1', 'nullable', 'date_format:H:i'],
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
            'pricing.required' => 'Harga untuk semua kombinasi stop wajib diisi.',
            'pricing.*.price.required' => 'Harga wajib diisi untuk setiap kombinasi.',
            'pricing.*.price.min' => 'Harga minimal Rp 1.000.',
            'pp_date.required_if' => 'Tanggal PP harus diisi jika PP diaktifkan.',
            'pp_time.required_if' => 'Jam PP harus diisi jika PP diaktifkan.',
            'pp_rest_hours.max' => 'Istirahat maksimal 48 jam.',
            'rest_days_before_rental.max' => 'Maksimal 30 hari.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'max_overload' => $this->travel_class === 'economy' ? ($this->max_overload ?? 2) : 0,
            'is_pp' => $this->boolean('is_pp'),
            'allow_cod' => $this->boolean('allow_cod'),
        ]);
    }
}