<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\ProgressTrackingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function show(Request $request, Course $course, Lesson $lesson, ProgressTrackingService $service): Response
    {
        $lesson->load('chapter.course');

        // Touch progress to record the visit
        if ($request->user()) {
            $service->touchLesson($request->user(), $lesson);
        }

        // Load the full course tree for sidebar navigation
        $course->load(['chapters.lessons']);

        // Get the user's completion status for lessons in this course
        $completedLessonIds = [];
        if ($request->user()) {
            $completedLessonIds = $request->user()
                ->progress()
                ->whereIn('lesson_id', $course->lessons()->pluck('lessons.id'))
                ->whereNotNull('completed_at')
                ->pluck('lesson_id')
                ->toArray();
        }

        // Find previous and next lessons
        $allLessons = $course->chapters->flatMap->lessons->sortBy('order_index')->values();
        $currentIndex = $allLessons->search(fn ($l) => $l->id === $lesson->id);
        $prevLesson = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
        $nextLesson = $currentIndex < $allLessons->count() - 1 ? $allLessons[$currentIndex + 1] : null;

        return Inertia::render('Lessons/Show', [
            'course' => $course,
            'lesson' => $lesson,
            'completedLessonIds' => $completedLessonIds,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
        ]);
    }
}
