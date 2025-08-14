<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

use App\Helpers\TyFunction;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            [
                'name'      => ['required', 'string', 'max:255'],
                'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password'  => ['required', 'confirmed', Rules\Password::defaults()],
                'username'  => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9]+$/', 'unique:users,username'],
                'phone'     => ['required', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
                'referral_code' => 'nullable|string|max:255',
                'terms'     => 'required|accepted',
            ],
            [
                'phone.regex' => 'The phone number must be in the format +[country code][number].',
                'email.unique' => 'The email address is already in use.',
                'username.unique' => 'The username is already taken.',
                'terms.accepted' => 'You must accept the terms and conditions.',
                'username.regex' => 'Usernames can only be lowercase letters and numbers',
            ]
        );
        $referrerId = null;
        if (!empty($request->referral_code)) {
            $referrerId = User::where('referral_code', $request->referral_code)->value('id');
        }


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_unique' => TyFunction::generateUserUnique(),
            'username' => $request->username,
            'phone' => $request->phone,
            'referral_code' => Str::substr($request->username, -4) . TyFunction::generateRandomString(4),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
