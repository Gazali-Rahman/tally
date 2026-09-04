<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ThemeSeeder::class);

        $theme = Theme::first();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'theme_id' => $theme->id,
        ]);

        $group = Group::create([
            'name' => 'Keuangan Keluarga',
        ]);

        $group->users()->attach($user->id, ['role' => 'owner']);
    }
}
