<?php

namespace App\Listeners;

use App\Events\LessonCompleted;
use Illuminate\Support\Facades\Log;

class NotifyAdminOfCompletion
{
    public function handle(LessonCompleted $event): void
    {
        Log::info('Lesson completed', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'lesson_id' => $event->lesson->id,
            'lesson_title' => $event->lesson->title,
        ]);
    }
}
