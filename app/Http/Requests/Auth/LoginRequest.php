<?php

namespace App\Http\Requests\Auth;

use App\Services\PhoneNumber;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Log-in form (route login.store): mobile number + password, at most 5 failed tries per minute. */
class LoginRequest extends FormRequest
{
    public const MAX_ATTEMPTS = 5;

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => PhoneNumber::normalize($this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('mobile number'),
            'password' => __('password'),
        ];
    }

    /** Sign the user in, or throw a validation error on the phone field. */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('phone', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['phone' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'phone' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    /** Limit per number and per device, so one person cannot lock out everyone on a shared network. */
    private function throttleKey(): string
    {
        return Str::transliterate($this->string('phone').'|'.$this->ip());
    }
}
