<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Sign-up: routes register and register.store (IA.md §5.2, guest only). */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'roles' => Role::selfAssignable()->orderBy('id')->get(),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'locale' => app()->getLocale(), // keep the language they signed up in
            ]);

            $user->roles()->attach(Role::whereIn('slug', $validated['roles'])->pluck('id'));

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', __('Welcome to Krishi Bondhu! Your account is ready.'));
    }
}
