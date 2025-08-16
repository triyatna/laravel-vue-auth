<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Support\AuthChallenge;
use App\Services\OtpService;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // $request->authenticate();

        // $request->session()->regenerate();

        // return redirect()->intended(route('dashboard', absolute: false));

        $request->authenticate();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $remember = $request->boolean('remember');
        Auth::logout();                      // lepas login — lanjut challenge

        if ($user->two_factor_confirmed_at) {
            AuthChallenge::initTotp($request, $user->user_unique, $remember, 300);
            return to_route('auth.totp.form');
        }

        app(OtpService::class)->send($user->email, $user->user_unique, 'login', 'email');
        AuthChallenge::initOtp($request, $user->user_unique, $remember, 'login', 'email');
        return to_route('auth.otp.form');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
