<?php

namespace Database\Seeders;

use App\Services\ContentIngestionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ContentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $path = storage_path('app/content');

        if (! File::isDirectory($path)) {
            $this->command->warn("Content directory not found at {$path}. Skipping content seeding.");
            return;
        }

        $service = new ContentIngestionService();
        $count = $service->syncFromContentDirectory($path);

        $this->command->info("Seeded {$count} lessons from markdown content.");
    }
}
