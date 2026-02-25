<?php

namespace App\Services;

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\User;
use App\Notifications\MentionNotification;

class MentionService
{
    /**
     * Extract mentioned user IDs from a Slate document.
     *
     * @param  array<int, mixed>  $document
     * @return list<int>
     */
    public function extractMentionedUserIds(array $document): array
    {
        $userIds = [];
        $this->walkNodes($document, $userIds);

        return array_values(array_unique($userIds));
    }

    /**
     * Notify users mentioned in a Slate document.
     *
     * @param  array<int, mixed>  $document
     */
    public function notifyMentionedUsers(array $document, User $author, Discussion $discussion, ?Reply $reply = null): void
    {
        $userIds = $this->extractMentionedUserIds($document);

        if (empty($userIds)) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->where('id', '!=', $author->id)
            ->where('is_deleted', false)
            ->get();

        foreach ($users as $user) {
            $user->notify(new MentionNotification($author, $discussion, $reply));
        }
    }

    /**
     * Recursively walk Slate nodes to find mention nodes.
     *
     * @param  array<int|string, mixed>  $nodes
     * @param  list<int>  $userIds
     */
    private function walkNodes(array $nodes, array &$userIds): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (isset($node['type']) && $node['type'] === 'mention' && isset($node['userId'])) {
                $userIds[] = (int) $node['userId'];
            }

            if (isset($node['children']) && is_array($node['children'])) {
                $this->walkNodes($node['children'], $userIds);
            }
        }
    }
}
