<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    public function create()
    {
        return inertia('auth/OtpVerify');
    }

    public function store(Request $request, OtpService $otp)
    {
        $request->validate(['code' => 'required|digits:6']);

        $u = User::where('user_unique', $request->session()->get('preauth_user_unique'))->firstOrFail();
        $ok = $otp->verify($u->email, 'login', $request->code); // ganti ke phone & 'sms' bila perlu

        if (! $ok) return back()->withErrors(['code' => 'OTP tidak valid atau kadaluarsa.']);

        // sukses → login final
        $remember = (bool) $request->session()->pull('remember', false);
        $request->session()->forget('preauth_user_unique');
        Auth::login($u, $remember);
        $u->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
