<?php
// File: app/Http/Requests/Api/CreateWithdrawalRequest.php
// Deskripsi: Validasi request untuk membuat penarikan dana

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'agency';
    }

    public function rules(): array
    {
        $minWithdrawal = config('gomad.minimal_withdrawal', 100000);

        return [
            'amount' => ['required', 'numeric', 'min:' . $minWithdrawal],
            'bank_name' => ['required', 'string', 'max:50'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_account_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah penarikan harus diisi.',
            'amount.min' => 'Minimal penarikan Rp ' . number_format(config('gomad.minimal_withdrawal', 100000), 0, ',', '.'),
            'bank_name.required' => 'Nama bank harus diisi.',
            'bank_account_number.required' => 'Nomor rekening harus diisi.',
            'bank_account_name.required' => 'Nama pemilik rekening harus diisi.',
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