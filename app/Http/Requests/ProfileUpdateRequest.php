<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidBrazilTaxId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'string', 'max:512'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'cpf' => ['nullable', 'string', 'max:20', new ValidBrazilTaxId],
            'cep' => ['nullable', 'string', 'max:15'],
            'address' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:60'],
            'state' => ['nullable', 'string', 'max:2', 'regex:/^[A-Za-z]{2}$/'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('state')) {
            $this->merge([
                'state' => strtoupper(trim((string) $this->input('state'))),
            ]);
        }
    }
}
