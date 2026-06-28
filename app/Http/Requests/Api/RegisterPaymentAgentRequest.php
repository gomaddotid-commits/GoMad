<?php
// File: app/Http/Requests/Api/RegisterPaymentAgentRequest.php
// Deskripsi: Validasi request untuk pendaftaran Warung GoMad

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterPaymentAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_name' => ['required', 'string', 'max:100'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'guard_name' => ['nullable', 'string', 'max:100'],
            'guard_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'maps_link' => ['nullable', 'url', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'agent_name.required' => 'Nama warung harus diisi.',
            'owner_name.required' => 'Nama pemilik harus diisi.',
            'owner_phone.required' => 'Nomor HP pemilik harus diisi.',
            'email.required' => 'Email harus diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password harus diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'address.required' => 'Alamat harus diisi.',
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