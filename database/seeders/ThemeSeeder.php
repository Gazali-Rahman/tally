<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Theme;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'name' => 'Default Ocean',
                'color_background' => '#ffffff',
                'color_primary' => '#003049',
                'color_secondary' => '#669bbc',
            ],
            [
                'name' => 'Forest Sage',
                'color_background' => '#fbfdfa',
                'color_primary' => '#1b4332',
                'color_secondary' => '#52b788',
            ],
            [
                'name' => 'Warm Terracotta',
                'color_background' => '#faf8f5',
                'color_primary' => '#7c2d12',
                'color_secondary' => '#ea580c',
            ],
            [
                'name' => 'Midnight Indigo',
                'color_background' => '#0f172a',
                'color_primary' => '#f8fafc',
                'color_secondary' => '#6366f1',
            ],
        ];

        foreach ($themes as $theme) {
            Theme::firstOrCreate(['name' => $theme['name']], $theme);
        }
    }
}
