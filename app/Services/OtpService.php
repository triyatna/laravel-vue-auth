<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function issue(?string $userUnique, string $identifier, string $channel, string $purpose, int $digits = 6, int $ttl = 5): OtpCode
    {
        $code = str_pad((string) random_int(0, 10 ** $digits - 1), $digits, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'user_unique' => $userUnique,
            'identifier' => $identifier,
            'channel' => $channel,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
            'meta' => ['ip' => request()->ip(), 'ua' => request()->userAgent()],
        ]);

        if ($channel === 'email') {
            Mail::raw("Code OTP: {$code} (valid for {$ttl} minutes).", function ($m) use ($identifier) {
                $m->to($identifier)->subject('Code OTP');
            });
        } else if ($channel === 'sms') {
            // Implement SMS sending logic here
        } else if ($channel === 'whatsapp') {
            // Implement WhatsApp sending logic here
        }

        return $otp;
    }

    public function verify(string $identifier, string $purpose, string $code): bool
    {
        $otp = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->orderByDesc('id')->first();

        if (!$otp || $otp->expires_at->isPast() || $otp->attempts >= 5) return false;

        $otp->increment('attempts');
        if (! Hash::check($code, $otp->code_hash)) return false;

        $otp->update(['used_at' => now()]);
        return true;
    }
}
