<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuthChallenge
{
    const KEY = 'challenge';

    public static function initOtp(Request $r, string $userUnique, bool $remember, string $purpose = 'login', string $channel = 'email'): void
    {
        $r->session()->put(self::KEY, [
            'type'        => 'otp',
            'user_unique' => $userUnique,
            'remember'    => $remember,
            'purpose'     => $purpose,
            'channel'     => $channel,
        ]);
    }

    public static function initTotp(Request $r, string $userUnique, bool $remember, int $ttlSeconds = 300): void
    {
        $r->session()->put(self::KEY, [
            'type'        => 'totp',
            'user_unique' => $userUnique,
            'remember'    => $remember,
            'until'       => now()->addSeconds($ttlSeconds)->unix(),
        ]);
    }

    public static function get(Request $r): ?array
    {
        $c = $r->session()->get(self::KEY);
        return is_array($c) ? $c : null;
    }

    public static function clear(Request $r): void
    {
        $r->session()->forget(self::KEY);
    }

    public static function pullRemember(Request $r): bool
    {
        $c = self::get($r);
        return (bool)($c['remember'] ?? false);
    }

    public static function finalizeLogin(Request $r, \App\Models\User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
        self::clear($r);
        $r->session()->regenerate();
    }
}
