<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityController extends Controller
{

    public function index(Request $request)
    {
        return inertia('settings/Security', [
            'twoFactorEnabled' => $request->user()->two_factor_confirmed_at !== null,
            'recoveryCodes' => $request->user()->two_factor_recovery_codes ?? [],
        ]);
    }
    public function totpSetup(TotpService $totp, Request $request)
    {
        $user = $request->user();
        $secret = $user->two_factor_secret ? decrypt($user->two_factor_secret) : $totp->generateSecret();

        if (! $user->two_factor_secret) {
            $user->update(['two_factor_secret' => encrypt($secret)]);
        }
        $svg = $totp->getQrSvg(config('app.name'), $user->email, $secret);
        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }

    public function totpEnable(Request $request, TotpService $totp)
    {
        $request->validate(['code' => 'required|digits:6', 'password' => ['required', 'current_password'],]);
        if (! $request->user()->two_factor_secret) {
            return back()->withErrors(['code' => '2FA secret not set.']);
        }
        $secret = decrypt($request->user()->two_factor_secret);
        if (! $totp->verify($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid 2FA OTP code.']);
        }

        $request->user()->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => collect(range(1, 8))->map(fn() => Str::upper(Str::random(10)))->all(),
        ]);
        return back()->with('status', '2FA enabled.');
    }

    public function totpDisable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $request->user()->update([
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
        return back()->with('status', '2FA disabled.');
    }
}
