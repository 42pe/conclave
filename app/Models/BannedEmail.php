<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_id',
        'banned_by',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bannedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public static function isBanned(string $email): bool
    {
        return static::where('email', strtolower($email))->exists();
    }
}
