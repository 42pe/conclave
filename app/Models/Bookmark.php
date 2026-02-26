<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    /** @var bool */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['user_id', 'discussion_id'];

    protected static function booted(): void
    {
        static::creating(function (Bookmark $bookmark) {
            $bookmark->created_at = now();
        });
    }

    /**
     * Get the user who created this bookmark.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bookmarked discussion.
     */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }
}
