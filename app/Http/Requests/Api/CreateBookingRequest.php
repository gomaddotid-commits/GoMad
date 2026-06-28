<?php
// File: app/Http/Requests/Api/CreateBookingRequest.php
// Deskripsi: Validasi request untuk membuat booking

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\RoutePricing;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Services\OverloadService;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'customer';
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'origin_stop_id' => ['required', 'integer', 'exists:route_stops,id'],
            'destination_stop_id' => ['required', 'integer', 'exists:route_stops,id', 'different:origin_stop_id'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_maps_link' => ['nullable', 'url', 'max:500'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'destination_address' => ['required', 'string', 'max:500'],
            'destination_maps_link' => ['nullable', 'url', 'max:500'],
            'destination_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'passengers' => ['required', 'array', 'min:1', 'max:10'],
            'passengers.*.name' => ['required', 'string', 'max:100'],
            'passengers.*.phone' => ['nullable', 'string', 'max:20'],
            'passengers.*.baggage_weight' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'payment_type' => ['required', 'in:midtrans,cash'],
            'special_notes' => ['nullable', 'string', 'max:500'],
            'promo_id' => ['nullable', 'integer', 'exists:promos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.required' => 'Jadwal harus dipilih.',
            'schedule_id.exists' => 'Jadwal tidak ditemukan.',
            'origin_stop_id.required' => 'Stop asal harus dipilih.',
            'destination_stop_id.required' => 'Stop tujuan harus dipilih.',
            'destination_stop_id.different' => 'Stop tujuan harus berbeda dengan stop asal.',
            'pickup_address.required' => 'Alamat penjemputan harus diisi.',
            'destination_address.required' => 'Alamat tujuan harus diisi.',
            'passengers.required' => 'Data penumpang harus diisi.',
            'passengers.min' => 'Minimal 1 penumpang.',
            'passengers.*.name.required' => 'Nama penumpang harus diisi.',
            'payment_type.required' => 'Metode pembayaran harus dipilih.',
            'payment_type.in' => 'Metode pembayaran tidak valid.',
            'promo_id.exists' => 'Promo tidak ditemukan.',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $schedule = Schedule::find($this->schedule_id);
            
            if ($schedule) {
                $originStop = RouteStop::find($this->origin_stop_id);
                $destStop = RouteStop::find($this->destination_stop_id);

                if ($originStop && $destStop) {
                    // Validate stop order
                    if ($originStop->stop_order >= $destStop->stop_order) {
                        $validator->errors()->add('origin_stop_id', 'Stop asal harus sebelum stop tujuan.');
                    }

                    // Validate pricing exists
                    $pricing = RoutePricing::where('schedule_id', $schedule->id)
                        ->where('origin_stop_id', $originStop->id)
                        ->where('destination_stop_id', $destStop->id)
                        ->first();

                    if (!$pricing) {
                        $validator->errors()->add('destination_stop_id', 'Harga untuk kombinasi rute ini belum tersedia.');
                    }
                }

                // Validate capacity
                $overloadService = app(OverloadService::class);
                if (!$overloadService->validateCapacity($schedule, count($this->passengers ?? []))) {
                    $validator->errors()->add('schedule_id', 'Jadwal sudah penuh.');
                }
            }
        });
    }
}

// End of file