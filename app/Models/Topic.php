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
    /** @use HasFactory<\Database\Factories\TopicFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => TopicVisibility::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Topic $topic) {
            if (empty($topic->slug)) {
                $topic->slug = static::generateUniqueSlug($topic->title);
            }
        });

        static::updating(function (Topic $topic) {
            if ($topic->isDirty('title') && ! $topic->isDirty('slug')) {
                $topic->slug = static::generateUniqueSlug($topic->title, $topic->id);
            }
        });
    }

    /**
     * Generate a unique slug from a title.
     */
    protected static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (static::query()
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
     * Get the discussions in this topic.
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * Get the user who created this topic.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to filter by visibility.
     */
    public function scopeVisibility(Builder $query, TopicVisibility $visibility): Builder
    {
        return $query->where('visibility', $visibility);
    }
}
