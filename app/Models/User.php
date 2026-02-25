<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'first_name',
        'last_name',
        'preferred_name',
        'bio',
        'avatar_path',
        'role',
        'is_deleted',
        'is_suspended',
        'deleted_at',
        'show_real_name',
        'show_email',
        'show_in_directory',
        'notify_replies',
        'notify_messages',
        'notify_mentions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['display_name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'is_deleted' => 'boolean',
            'is_suspended' => 'boolean',
            'deleted_at' => 'datetime',
            'show_real_name' => 'boolean',
            'show_email' => 'boolean',
            'show_in_directory' => 'boolean',
            'notify_replies' => 'boolean',
            'notify_messages' => 'boolean',
            'notify_mentions' => 'boolean',
        ];
    }

    /**
     * Get the user's display name.
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->is_deleted) {
                    return 'Deleted User';
                }

                return $this->preferred_name ?? $this->name;
            },
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::Moderator;
    }

    public function isAdminOrModerator(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    /**
     * Get the discussions created by the user.
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * Get the replies created by the user.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    /**
     * Get the media uploaded by the user.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Get the banned email records associated with this user.
     */
    public function bannedEmails(): HasMany
    {
        return $this->hasMany(BannedEmail::class);
    }

    /**
     * Get the conversations this user is a participant in.
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }
}
