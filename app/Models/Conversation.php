<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    /**
     * Get the participants in this conversation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * Get the participant records for this conversation.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Get the messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the latest message in this conversation.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Find an existing conversation between two users.
     */
    public static function between(User $user1, User $user2): ?self
    {
        return static::query()
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $user1->id))
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $user2->id))
            ->first();
    }

    /**
     * Scope conversations involving a specific user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', fn (Builder $q) => $q->where('user_id', $user->id));
    }
}
