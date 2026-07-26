<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            'https://www.instagram.com/moodateknologi.id',
            'https://www.tiktok.com/@moodateknologi.id',
            'https://www.facebook.com/mooda.id',
        ];

        foreach ($links as $i => $url) {
            SocialLink::firstOrCreate(
                ['url' => $url],
                ['sort_order' => $i + 1, 'is_active' => true]
            );
        }
    }
}
