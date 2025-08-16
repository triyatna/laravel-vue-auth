<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class OtpCode extends Model
{
    protected $guarded = [];
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the user associated with the OTP code.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_unique', 'user_unique');
    }

    public function scopeFor(Builder $q, string $userUnique, string $purpose, ?string $channel = null): Builder
    {
        $q->where('user_unique', $userUnique)->where('purpose', $purpose);
        if ($channel) $q->where('channel', $channel);
        return $q;
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('used_at')->where('expires_at', '>', now());
    }
}
