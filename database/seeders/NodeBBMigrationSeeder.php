<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Discussion;
use App\Models\Location;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NodeBBMigrationSeeder extends Seeder
{
    private string $dataDir;

    /** @var array<int, int> nodebb_uid → conclave user_id */
    private array $uidMap = [];

    /** @var array<int, int> nodebb_tid → conclave discussion_id */
    private array $tidMap = [];

    /** @var array<int, int> nodebb_pid → conclave reply_id */
    private array $pidMap = [];

    /** @var array<string, int> nodebb_tag → conclave topic_id */
    private array $tagMap = [];

    /** @var array<string, int> iso_code → location_id */
    private array $locationMap = [];

    /** @var array<string, true> tracks used usernames (lowercased) for uniqueness */
    private array $usedUsernames = [];

    /** @var array<string, true> tracks used emails (lowercased) for uniqueness */
    private array $usedEmails = [];

    public function run(): void
    {
        $this->dataDir = base_path('scraper/output');

        if (! File::isDirectory($this->dataDir)) {
            $this->command->error("Scraper output not found at {$this->dataDir}");
            $this->command->error('Run the scraper first: cd scraper && npm run scrape');

            return;
        }

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║         NodeBB → Conclave Migration Seeder              ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');

        $this->call(Development\LocationSeeder::class);
        $admin = $this->getOrCreateAdmin();
        $this->buildLocationMap();
        $this->importTopics($admin);
        $this->importUsers();
        $this->copyImages();
        $this->importDiscussions();
        $this->importReplies();
        $this->fixDiscussionStats();
        $this->printSummary();
    }

    private function getOrCreateAdmin(): User
    {
        $this->command->info("\n── Admin User ──");

        $admin = User::where('role', UserRole::Admin)->first();

        if ($admin) {
            $this->command->info("  Using existing admin: {$admin->email}");

            return $admin;
        }

        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@conclave.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $this->command->info("  Created admin user: {$admin->email}");

        return $admin;
    }

    private function buildLocationMap(): void
    {
        $this->command->info("\n── Building Location Map ──");

        $locations = Location::all();

        foreach ($locations as $location) {
            $this->locationMap[$location->iso_code] = $location->id;
        }

        $this->command->info("  Mapped {$locations->count()} locations");
    }

    private function importTopics(User $admin): void
    {
        $this->command->info("\n── Importing Topics ──");

        $tags = $this->loadJson('tags.json');

        foreach ($tags as $tag) {
            $topic = Topic::create([
                'title' => $tag['title'],
                'slug' => $tag['slug'],
                'description' => $tag['description'] ?: null,
                'icon' => $tag['icon'],
                'visibility' => $tag['visibility'],
                'sort_order' => $tag['sort_order'],
                'created_by' => $admin->id,
            ]);

            $this->tagMap[$tag['nodebb_tag']] = $topic->id;
            $this->command->info("  {$topic->title} → ID {$topic->id}");
        }
    }

    private function importUsers(): void
    {
        $this->command->info("\n── Importing Users ──");

        $users = $this->loadJson('users.json');
        $total = count($users);
        $this->command->info("  Processing {$total} users...");

        // Pre-populate used usernames/emails from existing DB records
        DB::table('users')->select('username', 'email')->get()->each(function ($row) {
            $this->usedUsernames[strtolower($row->username)] = true;
            $this->usedEmails[strtolower($row->email)] = true;
        });

        $defaultPassword = Hash::make(Str::random(32));
        $imported = 0;
        $skipped = 0;

        foreach (array_chunk($users, 500) as $chunkIndex => $chunk) {
            $rows = [];

            foreach ($chunk as $userData) {
                $emailLower = strtolower(trim($userData['email']));

                if (empty($userData['email']) || isset($this->usedEmails[$emailLower])) {
                    $skipped++;

                    continue;
                }

                $username = $this->sanitizeUsername($userData['username']);

                if ($username === null) {
                    $skipped++;

                    continue;
                }

                $this->usedUsernames[strtolower($username)] = true;
                $this->usedEmails[$emailLower] = true;

                $rows[] = [
                    'username' => $username,
                    'email' => $userData['email'],
                    'name' => $userData['name'] ?: $userData['username'],
                    'first_name' => $userData['first_name'] ?: null,
                    'last_name' => $userData['last_name'] ?: null,
                    'bio' => $userData['bio'] ?: null,
                    'avatar_path' => $userData['avatar_local_path']
                        ? "uploads/migrated/{$userData['avatar_local_path']}"
                        : null,
                    'password' => $defaultPassword,
                    'email_verified_at' => $this->toDatetime($userData['joined_at']),
                    'role' => UserRole::User->value,
                    'is_deleted' => false,
                    'is_suspended' => $userData['is_banned'] ?? false,
                    'show_real_name' => true,
                    'show_email' => false,
                    'show_in_directory' => true,
                    'notify_replies' => true,
                    'notify_messages' => true,
                    'notify_mentions' => true,
                    'created_at' => $this->toDatetime($userData['joined_at']),
                    'updated_at' => $this->toDatetime($userData['joined_at']),
                ];

                $imported++;
            }

            if (! empty($rows)) {
                DB::table('users')->insert($rows);
            }

            $progress = min(($chunkIndex + 1) * 500, $total);
            $this->command->info("  Progress: {$progress}/{$total}");
        }

        // Build UID map by querying back via email
        $this->command->info('  Building UID mapping...');
        $emailToId = DB::table('users')
            ->select('id', 'email')
            ->get()
            ->pluck('id', 'email')
            ->toArray();

        foreach ($users as $userData) {
            if (isset($emailToId[$userData['email']])) {
                $this->uidMap[$userData['nodebb_uid']] = $emailToId[$userData['email']];
            }
        }

        $this->command->info("  Imported: {$imported}, Skipped: {$skipped}");
        $this->command->info('  UID mappings: '.count($this->uidMap));
    }

    private function importDiscussions(): void
    {
        $this->command->info("\n── Importing Discussions ──");

        $discussions = $this->loadJson('discussions.json');
        $total = count($discussions);
        $this->command->info("  Processing {$total} discussions...");

        $imported = 0;
        $skipped = 0;

        foreach ($discussions as $data) {
            $topicId = $this->tagMap[$data['nodebb_tag']] ?? null;
            $userId = $this->uidMap[$data['nodebb_uid']] ?? null;

            if (! $topicId) {
                $skipped++;

                continue;
            }

            // Remap @mention UIDs in Slate body
            $body = $this->remapMentionUids($data['body_slate']);

            // Map location from state/country
            $locationId = $this->mapLocationId($data['state'] ?? '', $data['country'] ?? '');

            // Ensure slug uniqueness within topic
            $slug = $data['slug'];
            if (Discussion::where('topic_id', $topicId)->where('slug', $slug)->exists()) {
                $slug .= '-'.Str::random(4);
            }

            $discussion = new Discussion;
            $discussion->fill([
                'topic_id' => $topicId,
                'user_id' => $userId,
                'location_id' => $locationId,
                'title' => $data['title'],
                'slug' => $slug,
                'body' => $body,
                'is_pinned' => $data['is_pinned'],
                'is_locked' => $data['is_locked'],
                'view_count' => $data['view_count'],
                'reply_count' => 0,
                'last_reply_at' => null,
            ]);
            $discussion->created_at = $this->toDatetime($data['created_at']);
            $discussion->updated_at = $this->toDatetime($data['updated_at'] ?? $data['created_at']);
            $discussion->save();

            $this->tidMap[$data['nodebb_tid']] = $discussion->id;
            $imported++;

            if ($imported % 100 === 0) {
                $this->command->info("  Progress: {$imported}/{$total}");
            }
        }

        $this->command->info("  Imported: {$imported}, Skipped: {$skipped}");
    }

    private function importReplies(): void
    {
        $this->command->info("\n── Importing Replies ──");

        $replies = $this->loadJson('replies.json');
        $total = count($replies);
        $this->command->info("  Processing {$total} replies...");

        // Sort by created_at so stats are correct after bulk insert
        usort($replies, fn ($a, $b) => strcmp($a['created_at'], $b['created_at']));

        // Group by depth for ordered insertion (parents before children)
        $byDepth = [[], [], []];
        foreach ($replies as $data) {
            $depth = min($data['depth'], 2);
            $byDepth[$depth][] = $data;
        }

        $imported = 0;
        $skipped = 0;

        // Disable reply observer — we'll fix discussion stats with SQL after
        Reply::withoutEvents(function () use ($byDepth, &$imported, &$skipped) {
            for ($depth = 0; $depth <= 2; $depth++) {
                foreach ($byDepth[$depth] as $data) {
                    $discussionId = $this->tidMap[$data['nodebb_tid']] ?? null;
                    $userId = $this->uidMap[$data['nodebb_uid']] ?? null;

                    if (! $discussionId) {
                        $skipped++;

                        continue;
                    }

                    // Map parent_id from NodeBB pid
                    $parentId = null;
                    if ($data['nodebb_parent_pid'] && isset($this->pidMap[$data['nodebb_parent_pid']])) {
                        $parentId = $this->pidMap[$data['nodebb_parent_pid']];
                    }

                    // Remap @mention UIDs in Slate body
                    $body = $this->remapMentionUids($data['body_slate']);

                    $reply = new Reply;
                    $reply->fill([
                        'discussion_id' => $discussionId,
                        'user_id' => $userId,
                        'parent_id' => $parentId,
                        'depth' => $depth,
                        'body' => $body,
                    ]);
                    $reply->created_at = $this->toDatetime($data['created_at']);
                    $reply->updated_at = $this->toDatetime($data['updated_at'] ?? $data['created_at']);
                    $reply->save();

                    $this->pidMap[$data['nodebb_pid']] = $reply->id;
                    $imported++;
                }

                $this->command->info("  Depth {$depth}: ".count($byDepth[$depth]).' replies');
            }
        });

        $this->command->info("  Imported: {$imported}, Skipped: {$skipped}");
    }

    private function fixDiscussionStats(): void
    {
        $this->command->info("\n── Fixing Discussion Stats ──");

        // Update reply_count and last_reply_at from actual reply data
        DB::statement('
            UPDATE discussions d
            LEFT JOIN (
                SELECT discussion_id,
                       COUNT(*) as reply_count,
                       MAX(created_at) as last_reply_at
                FROM replies
                GROUP BY discussion_id
            ) r ON r.discussion_id = d.id
            SET d.reply_count = COALESCE(r.reply_count, 0),
                d.last_reply_at = r.last_reply_at
        ');

        $this->command->info('  Updated reply_count and last_reply_at for all discussions');
    }

    private function copyImages(): void
    {
        $this->command->info("\n── Copying Images ──");

        $source = base_path('scraper/output/images');
        $destination = storage_path('app/public/uploads/migrated');

        if (! File::isDirectory($source)) {
            $this->command->warn('  No images directory found, skipping...');

            return;
        }

        File::ensureDirectoryExists($destination);
        File::copyDirectory($source, $destination);

        $count = count(File::allFiles($destination));
        $this->command->info("  Copied {$count} files to storage/app/public/uploads/migrated/");
    }

    // ── Helper Methods ──────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadJson(string $filename): array
    {
        $path = "{$this->dataDir}/{$filename}";

        if (! File::exists($path)) {
            $this->command->error("  File not found: {$path}");

            return [];
        }

        return json_decode(File::get($path), true);
    }

    private function sanitizeUsername(string $username): ?string
    {
        // Replace invalid characters (dots, spaces, etc.) with underscores
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);

        // Ensure starts with a letter
        if (! preg_match('/^[a-zA-Z]/', $clean)) {
            $clean = 'u'.$clean;
        }

        // Collapse consecutive underscores/hyphens
        $clean = preg_replace('/[_-]{2,}/', '_', $clean);

        // Trim trailing underscores/hyphens
        $clean = rtrim($clean, '_-');

        // Pad to minimum length (5 chars)
        if (strlen($clean) < 5) {
            $clean = str_pad($clean, 5, strtolower(Str::random(3)));
        }

        // Truncate to max length (16 chars)
        if (strlen($clean) > 16) {
            $clean = rtrim(substr($clean, 0, 16), '_-');
        }

        // Final validation
        if (strlen($clean) < 5 || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_-]+$/', $clean)) {
            return null;
        }

        // Ensure uniqueness
        $base = $clean;
        $n = 1;
        while (isset($this->usedUsernames[strtolower($clean)])) {
            $suffix = (string) $n;
            $clean = rtrim(substr($base, 0, 16 - strlen($suffix)), '_-').$suffix;
            $n++;

            if ($n > 999) {
                return null;
            }
        }

        return $clean;
    }

    /**
     * Recursively remap NodeBB UIDs to Conclave user IDs in Slate mention nodes.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function remapMentionUids(array $nodes): array
    {
        return array_map(function (array $node) {
            if (isset($node['type']) && $node['type'] === 'mention' && isset($node['userId'])) {
                $node['userId'] = $this->uidMap[$node['userId']] ?? $node['userId'];
            }

            if (isset($node['children']) && is_array($node['children'])) {
                $node['children'] = $this->remapMentionUids($node['children']);
            }

            return $node;
        }, $nodes);
    }

    private function mapLocationId(string $state, string $country): ?int
    {
        // Map US state codes to location IDs (e.g., 'OR' → 'US-OR')
        if ($state && $country === 'US') {
            return $this->locationMap["US-{$state}"] ?? null;
        }

        // Map country codes directly
        if ($country) {
            return $this->locationMap[$country] ?? null;
        }

        return null;
    }

    /**
     * Convert an ISO 8601 timestamp to MySQL datetime format.
     */
    private function toDatetime(?string $iso): ?string
    {
        if (! $iso) {
            return null;
        }

        return Carbon::parse($iso)->format('Y-m-d H:i:s');
    }

    private function printSummary(): void
    {
        $this->command->info("\n── Migration Summary ──");
        $this->command->info('  Topics:      '.count($this->tagMap));
        $this->command->info('  Users:       '.count($this->uidMap));
        $this->command->info('  Discussions: '.count($this->tidMap));
        $this->command->info('  Replies:     '.count($this->pidMap));
        $this->command->newLine();
        $this->command->info('Done!');
    }
}
