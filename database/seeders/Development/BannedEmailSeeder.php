<?php

namespace Database\Seeders\Development;

use App\Models\BannedEmail;
use App\Models\User;
use Illuminate\Database\Seeder;

class BannedEmailSeeder extends Seeder
{
    /**
     * Seed development banned email entries.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();

        BannedEmail::create([
            'email' => 'spammer@example.com',
            'banned_by' => $admin->id,
            'reason' => 'Repeated spam in forum discussions.',
        ]);

        BannedEmail::create([
            'email' => 'troll@example.com',
            'banned_by' => $admin->id,
            'reason' => 'Persistent harassment of other members.',
        ]);

        BannedEmail::create([
            'email' => 'banned@example.com',
            'banned_by' => $admin->id,
            'reason' => null,
        ]);
    }
}
