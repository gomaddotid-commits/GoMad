<?php
// File: app/Http/Requests/Web/ScheduleRequest.php
// Deskripsi: Validasi request web untuk membuat jadwal

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
            'pricing' => ['required', 'array', 'min:1'],
            'pricing.*.origin_stop_id' => ['required', 'integer'],
            'pricing.*.destination_stop_id' => ['required', 'integer'],
            'pricing.*.price' => ['required', 'numeric', 'min:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_date.after_or_equal' => 'Jadwal harus H-30 sebelum keberangkatan.',
            'pricing.required' => 'Harga untuk semua kombinasi stop wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'max_overload' => $this->travel_class === 'economy' ? ($this->max_overload ?? 2) : 0,
        ]);
    }
}

// End of file