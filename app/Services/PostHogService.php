<?php

namespace App\Services;

use PostHog\PostHog;

class PostHogService
{
    private bool $enabled;

    public function __construct(
        private string $apiKey,
        private string $host,
    ) {
        $this->enabled = $this->apiKey !== '';

        if ($this->enabled) {
            PostHog::init($this->apiKey, ['host' => $this->host]);
        }
    }

    /**
     * Capture an event for a distinct user.
     *
     * @param  array<string, mixed>  $properties
     */
    public function capture(string $distinctId, string $event, array $properties = []): void
    {
        if (! $this->enabled) {
            return;
        }

        PostHog::capture([
            'distinctId' => $distinctId,
            'event' => $event,
            'properties' => $properties,
        ]);
    }

    /**
     * Identify a user with given properties.
     *
     * @param  array<string, mixed>  $properties
     */
    public function identify(string $distinctId, array $properties = []): void
    {
        if (! $this->enabled) {
            return;
        }

        PostHog::identify([
            'distinctId' => $distinctId,
            'properties' => $properties,
        ]);
    }
}
