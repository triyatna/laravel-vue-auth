<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\Request;
use App\Support\AuthChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;

class TotpController extends Controller
{
    public function create()
    {
        return inertia('auth/TotpVerify');
    }

    // public function store(Request $request, TotpService $totp)
    // {
    //     $request->validate(['code' => 'required|digits:6']);

    //     $u = User::where('user_unique', $request->session()->get('preauth_user_unique'))->firstOrFail();
    //     if (! $u->two_factor_enabled || ! $u->two_factor_secret) {
    //         return back()->withErrors(['code' => '2FA tidak aktif untuk akun ini.']);
    //     }

    //     $secret = decrypt($u->two_factor_secret);
    //     if (! $totp->verify($secret, $request->code)) {
    //         return back()->withErrors(['code' => 'Kode TOTP salah.']);
    //     }

    //     // sukses → login final
    //     $remember = (bool) $request->session()->pull('remember', false);
    //     $request->session()->forget('preauth_user_unique');
    //     Auth::login($u, $remember);
    //     $u->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

    //     return redirect()->intended(route('dashboard', absolute: false));
    // }
    public function store(Request $request, TotpService $totp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'regex:/^\d{6}$/']], ['code.regex' => 'OTP must be 6 digits.']);

        $c = AuthChallenge::get($request);
        if (! $c || ($c['type'] ?? null) !== 'totp') return to_route('login');

        $user = User::where('user_unique', $c['user_unique'])->firstOrFail();
        if (! $user->two_factor_secret || ! $user->two_factor_confirmed_at) {
            AuthChallenge::clear($request);
            return to_route('login');
        }

        $secret = decrypt($user->two_factor_secret);
        if (! $totp->verify($secret, (string)$request->string('code'))) {
            return back()->withErrors(['code' => 'Invalid authentication code.'])->onlyInput('code');
        }

        AuthChallenge::finalizeLogin($request, $user, AuthChallenge::pullRemember($request));
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
