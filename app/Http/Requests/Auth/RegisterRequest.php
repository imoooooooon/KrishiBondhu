<?php

namespace App\Http\Requests\Auth;

use App\Models\Role;
use App\Services\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Sign-up form (route register.store). */
class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'email' => $this->filled('email') ? strtolower(trim($this->input('email'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN, Rule::unique('users', 'phone')],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'slug')->whereNot('slug', Role::ADMIN)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('Enter an 11-digit Bangladeshi mobile number, like 01712345678.'),
            'phone.unique' => __('An account with this mobile number already exists. Log in instead.'),
            'roles.required' => __('Choose at least one way you will use Krishi Bondhu.'),
            'roles.*.exists' => __('Choose roles from the list.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('name'),
            'phone' => __('mobile number'),
            'email' => __('email'),
            'password' => __('password'),
            'roles' => __('roles'),
        ];
    }
}
