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


        $user = $request->resolveUser();
        $request->session()->put('preauth_user', $user->user_unique);
        $request->session()->put('remember', $request->boolean('remember'));

        // TOTP diutamakan jika aktif
        if ($user->two_factor_enabled && $user->two_factor_secret) {
            return to_route('auth.totp.form')->with('status', '2FA is enabled. Please enter your TOTP code.');
        }

        // Jika tidak aktif TOTP → kirim OTP via email (atau SMS kalau phone tersedia)
        app(OtpService::class)->issue(
            userUnique: $user->user_unique,
            identifier: $user->email,     // atau $user->phone (E.164) jika mau SMS
            channel: 'email',
            purpose: 'login'
        );

        return to_route('auth.otp.form')->with('status', 'OTP Code has been sent to your email.');
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
