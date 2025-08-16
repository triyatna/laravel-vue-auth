<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\RedirectResponse;
use App\Support\AuthChallenge;

class OtpController extends Controller
{
    public function create()
    {
        return inertia('auth/OtpVerify');
    }

    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'regex:/^\d{6}$/']], ['code.regex' => 'OTP must be 6 digits.']);
        $c = AuthChallenge::get($request);
        if (! $c || ($c['type'] ?? null) !== 'otp') return to_route('login');

        $key = 'otp:verify:' . $c['user_unique'] . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $sec = RateLimiter::availableIn($key);
            return back()->withErrors(['code' => "Too many attempts. Try again in {$sec}s."])->setStatusCode(429);
        }

        $result = $otp->verifyDetailed($c['user_unique'], $c['purpose'] ?? 'login', (string)$request->string('code'), $c['channel'] ?? null);

        if (! $result['ok']) {
            RateLimiter::hit($key, 60);
            $msg = $result['reason'] === 'expired' ? 'Your OTP has expired. Please request a new code.' : 'Invalid OTP code.';
            return back()->withErrors(['code' => $msg])->onlyInput('code');
        }

        RateLimiter::clear($key);

        $user = User::where('user_unique', $c['user_unique'])->firstOrFail();
        AuthChallenge::finalizeLogin($request, $user, AuthChallenge::pullRemember($request));
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $c = AuthChallenge::get($request);
        if (! $c || ($c['type'] ?? null) !== 'otp') return to_route('login');

        $user = User::where('user_unique', $c['user_unique'])->firstOrFail();
        $otp->send($user->user_unique, $c['purpose'] ?? 'login', $c['channel'] ?? 'email', $user->email);

        return back()->with('status', 'A new code has been sent.');
    }
}
