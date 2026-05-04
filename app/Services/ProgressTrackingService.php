<?php

namespace App\Services;

use App\Events\LessonCompleted;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;

class ProgressTrackingService
{
    public function markComplete(User $user, Lesson $lesson, ?int $score = null): UserProgress
    {
        $progress = UserProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'completed_at' => now(),
                'score' => $score,
                'last_accessed_at' => now(),
            ]
        );

        LessonCompleted::dispatch($user, $lesson);

        return $progress;
    }

    public function touchLesson(User $user, Lesson $lesson): void
    {
        UserProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['last_accessed_at' => now()]
        );
    }

    public function getCourseProgress(User $user, Course $course): array
    {
        $lessonIds = $course->lessons()->pluck('lessons.id');
        $total = $lessonIds->count();

        if ($total === 0) {
            return ['total' => 0, 'completed' => 0, 'pct' => 0];
        }

        $completed = UserProgress::whereIn('lesson_id', $lessonIds)
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pct' => (int) round($completed / $total * 100),
        ];
    }
}
