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
            'metadata' => ['ip' => request()->ip(), 'ua' => request()->userAgent()],
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

    // public function verify(string $identifier, string $purpose, string $code): bool
    // {
    //     $otp = OtpCode::where('identifier', $identifier)
    //         ->where('purpose', $purpose)
    //         ->whereNull('used_at')
    //         ->orderByDesc('id')->first();

    //     if (!$otp || $otp->expires_at->isPast() || $otp->attempts >= 5) return false;

    //     $otp->increment('attempts');
    //     if (! Hash::check($code, $otp->code_hash)) return false;

    //     $otp->update(['used_at' => now()]);
    //     return true;
    // }

    protected function key(string $identifier, string $purpose): string
    {
        return "otp:{$purpose}:" . sha1($identifier);
    }
    protected function hash(string $code, string $userUnique, string $purpose, ?string $channel): string
    {
        return hash_hmac('sha256', "{$code}|{$userUnique}|{$purpose}|{$channel}", config('app.key'));
    }

    public function send(string $identifier, string $userUnique, string $purpose, string $channel): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'identifier'  => $identifier,
            'user_unique' => $userUnique,
            'purpose'     => $purpose,
            'channel'     => $channel,
            'code_hash'   => $this->hash($code, $userUnique, $purpose, $channel),
            'expires_at'  => now()->addMinutes(5),
            'metadata'    => ['ip' => request()->ip(), 'ua' => request()->userAgent()],
        ]);

        if ($channel === 'email') {
            Mail::raw("Your OTP verification code is: {$code}", fn($m) => $m
                ->to($identifier)
                ->subject(config('app.name') . ' OTP Verification'));
        }
        // Untuk SMS, kirim via provider Anda di sini
    }

    public function verifyDetailed(string $userUnique, string $purpose, string $code, ?string $channel = null): array
    {
        $row = OtpCode::for($userUnique, $purpose, $channel)->orderByDesc('id')->first();
        if (! $row || $row->used_at || $row->expires_at?->isPast()) {
            return ['ok' => false, 'reason' => 'expired'];
        }

        $ok = hash_equals($row->code_hash, $this->hash($code, $userUnique, $purpose, $channel));
        if (! $ok) {
            return ['ok' => false, 'reason' => 'invalid'];
        }

        $row->forceFill(['used_at' => now()])->save();
        return ['ok' => true, 'reason' => null];
    }
}
