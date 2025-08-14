<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
