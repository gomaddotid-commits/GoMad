<?php
// File: app/Http/Requests/Web/WithdrawalRequest.php
// Deskripsi: Validasi request web untuk penarikan dana

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'agency';
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:' . config('gomad.minimal_withdrawal', 100000)],
            'bank_name' => ['required', 'string', 'max:50'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_account_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Minimal penarikan Rp ' . number_format(config('gomad.minimal_withdrawal', 100000), 0, ',', '.'),
        ];
    }
}

// End of file