<?php

namespace App\Providers;

use App\Events\LessonCompleted;
use App\Listeners\NotifyAdminOfCompletion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production to fix Mixed Content errors
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        Vite::prefetch(concurrency: 3);

        Event::listen(LessonCompleted::class, NotifyAdminOfCompletion::class);
    }
}
