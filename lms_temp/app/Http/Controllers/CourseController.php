<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\ProgressTrackingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Course::where('is_published', true)
            ->orderBy('order_index')
            ->withCount(['chapters', 'lessons'])
            ->get();

        $progress = [];
        if ($request->user()) {
            $service = new ProgressTrackingService();
            foreach ($courses as $course) {
                $progress[$course->id] = $service->getCourseProgress($request->user(), $course);
            }
        }

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'progress' => $progress,
        ]);
    }

    public function show(Course $course): Response
    {
        $course->load(['chapters.lessons']);

        return Inertia::render('Courses/Show', [
            'course' => $course,
        ]);
    }
}
