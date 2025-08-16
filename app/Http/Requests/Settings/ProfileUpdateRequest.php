<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[1-9]\d{7,14}$/',
                Rule::unique(User::class, 'phone')->ignore($this->user()->id),
            ],
        ];
    }
    protected function prepareForValidation(): void
    {
        $email = (string) str($this->input('email', ''))->lower()->value();

        // normalisasi phone:
        $raw    = (string) $this->input('phone', '');
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            $normalized = null;
        } else {
            $normalized = str_starts_with($digits, '0')
                ? '62' . ltrim($digits, '0')
                : $digits;
        }

        $this->merge([
            'email' => $email,
            'phone' => $normalized,
        ]);
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor telepon harus dalam format internasional (contoh: 628123456789), 8–15 digit tanpa awalan 0.',
        ];
    }
}
