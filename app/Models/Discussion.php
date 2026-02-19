<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Discussion extends Model
{
    /** @use HasFactory<\Database\Factories\DiscussionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body' => 'array',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'last_reply_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Discussion $discussion) {
            if (empty($discussion->slug)) {
                $discussion->slug = static::generateUniqueSlug(
                    $discussion->title,
                    $discussion->topic_id,
                );
            }
        });

        static::updating(function (Discussion $discussion) {
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
     * Generate a unique slug scoped to a topic.
     */
    protected static function generateUniqueSlug(string $title, int $topicId, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 2;

        while (static::query()
            ->where('topic_id', $topicId)
            ->where('slug', $slug)
            ->when($excludeId, fn (Builder $query) => $query->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $original.'-'.$count;
            $count++;
        }

        return $slug;
    }

    /**
     * Get the topic this discussion belongs to.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the user who created this discussion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location for this discussion.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the replies for this discussion.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    /**
     * Get the media attached to this discussion.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Scope a query to only include pinned discussions.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeByLocation(Builder $query, int $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }
}
