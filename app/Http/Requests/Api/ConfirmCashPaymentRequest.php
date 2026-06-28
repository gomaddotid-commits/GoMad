<?php
// File: app/Http/Requests/Api/ConfirmCashPaymentRequest.php
// Deskripsi: Validasi request untuk konfirmasi pembayaran cash

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfirmCashPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'payment_agent';
    }

    public function rules(): array
    {
        return [
            'payment_code' => ['required', 'string', 'max:30'],
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_code.required' => 'Kode pembayaran harus diisi.',
            'pin.required' => 'PIN harus diisi.',
            'pin.size' => 'PIN harus 6 digit.',
            'pin.regex' => 'PIN hanya boleh angka.',
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