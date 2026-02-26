<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends Model
{
    /** @var bool */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['user_id'];

    protected static function booted(): void
    {
        static::creating(function (Like $like) {
            $like->created_at = now();
        });
    }

    /**
     * Get the user who created this like.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the likeable model.
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }
}
