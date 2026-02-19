<?php

namespace App\Models;

use App\Observers\ReplyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ReplyObserver::class)]
class Reply extends Model
{
    use HasFactory;

    protected $fillable = [
        'discussion_id',
        'user_id',
        'parent_id',
        'depth',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'depth' => 'integer',
        ];
    }

    /**
     * The discussion this reply belongs to.
     */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    /**
     * The user who wrote this reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The parent reply (if nested).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Reply::class, 'parent_id');
    }

    /**
     * Child replies.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Reply::class, 'parent_id');
    }
}
