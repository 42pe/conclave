<?php

namespace App\Services;

use App\Models\User;
use PostHog\PostHog;

class PostHogService
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('posthog.enabled');

        if ($this->enabled && config('posthog.api_key')) {
            PostHog::init(config('posthog.api_key'), [
                'host' => config('posthog.host'),
            ]);
        }
    }

    public function capture(User $user, string $event, array $properties = []): void
    {
        if (! $this->enabled) {
            return;
        }

        PostHog::capture([
            'distinctId' => (string) $user->id,
            'event' => $event,
            'properties' => $properties,
        ]);
    }

    public function identify(User $user): void
    {
        if (! $this->enabled) {
            return;
        }

        PostHog::identify([
            'distinctId' => (string) $user->id,
            'properties' => [
                'username' => $user->username,
                'role' => $user->role->value,
                'email' => $user->email,
            ],
        ]);
    }
}
