<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TotpController extends Controller
{
    public function create()
    {
        return inertia('auth/TotpVerify');
    }

    public function store(Request $request, TotpService $totp)
    {
        $request->validate(['code' => 'required|digits:6']);

        $u = User::where('user_unique', $request->session()->get('preauth_user_unique'))->firstOrFail();
        if (! $u->two_factor_enabled || ! $u->two_factor_secret) {
            return back()->withErrors(['code' => '2FA tidak aktif untuk akun ini.']);
        }

        $secret = decrypt($u->two_factor_secret);
        if (! $totp->verify($secret, $request->code)) {
            return back()->withErrors(['code' => 'Kode TOTP salah.']);
        }

        // sukses → login final
        $remember = (bool) $request->session()->pull('remember', false);
        $request->session()->forget('preauth_user_unique');
        Auth::login($u, $remember);
        $u->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
