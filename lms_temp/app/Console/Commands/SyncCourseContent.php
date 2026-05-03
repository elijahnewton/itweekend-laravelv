<?php

namespace App\Console\Commands;

use App\Services\ContentIngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncCourseContent extends Command
{
    protected $signature = 'content:sync {--path= : Custom path to content directory}';

    protected $description = 'Sync course content from markdown files in storage/app/content';

    public function handle(ContentIngestionService $service): int
    {
        $path = $this->option('path') ?? storage_path('app/content');

        if (! File::isDirectory($path)) {
            $this->error("Content directory not found: {$path}");

            return Command::FAILURE;
        }

        $this->info("Syncing content from: {$path}");

        $count = $service->syncFromContentDirectory($path);

        $this->info("✓ Synced {$count} lessons successfully.");

        return Command::SUCCESS;
    }
}
