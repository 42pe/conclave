<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Discussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'user_id',
        'location_id',
        'title',
        'slug',
        'body',
        'is_pinned',
        'is_locked',
        'reply_count',
        'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'reply_count' => 'integer',
            'last_reply_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Discussion $discussion): void {
            if (empty($discussion->slug)) {
                $discussion->slug = static::generateUniqueSlug(
                    $discussion->title,
                    $discussion->topic_id,
                );
            }
        });

        static::updating(function (Discussion $discussion): void {
            if ($discussion->isDirty('title') && ! $discussion->isDirty('slug')) {
                $discussion->slug = static::generateUniqueSlug(
                    $discussion->title,
                    $discussion->topic_id,
                    $discussion->id,
                );
            }
        });
    }

    /**
     * Generate a unique slug scoped to the topic.
     */
    public static function generateUniqueSlug(string $title, int $topicId, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::query()
            ->where('topic_id', $topicId)
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * The topic this discussion belongs to.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * The user who created this discussion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The location associated with this discussion.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The replies to this discussion.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    /**
     * Scope: pinned discussions first.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: filter by location.
     */
    public function scopeByLocation(Builder $query, int $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }
}
