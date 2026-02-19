<?php

namespace App\Models;

use App\Enums\TopicVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'icon',
        'header_image_path',
        'visibility',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => TopicVisibility::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Topic $topic): void {
            if (empty($topic->slug)) {
                $topic->slug = static::generateUniqueSlug($topic->title);
            }
        });

        static::updating(function (Topic $topic): void {
            if ($topic->isDirty('title') && ! $topic->isDirty('slug')) {
                $topic->slug = static::generateUniqueSlug($topic->title, $topic->id);
            }
        });
    }

    /**
     * Generate a unique slug from the given title.
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn(Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * The user who created this topic.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The discussions in this topic.
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * Scope: filter by visibility.
     */
    public function scopeVisibility(Builder $query, TopicVisibility $visibility): Builder
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope: only publicly visible topics.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', TopicVisibility::Public);
    }
}
