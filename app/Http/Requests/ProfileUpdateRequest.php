<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $publicHandle = $this->input('public_handle');

        $this->merge([
            'public_handle' => filled($publicHandle)
                ? Str::slug((string) $publicHandle)
                : null,
        ]);
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
            'public_handle' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::notIn(User::reservedPublicHandles()),
                Rule::unique(User::class, 'public_handle')->ignore($this->user()->id),
            ],
        ];
    }
}
