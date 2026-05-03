<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    $courses = \App\Models\Course::where('is_published', true)->orderBy('order_index')->get();
    $progress = [];
    $service = new \App\Services\ProgressTrackingService();
    foreach ($courses as $course) {
        $p = $service->getCourseProgress($user, $course);
        if ($p['total'] > 0) {
            $progress[$course->id] = $p;
        }
    }
    return Inertia::render('Dashboard', [
        'courses' => $courses,
        'progress' => $progress,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Courses (public)
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Lessons & Progress (authenticated)
    Route::get('/courses/{course:slug}/lessons/{lesson:slug}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('/progress/complete', [ProgressController::class, 'complete'])->name('progress.complete');
});

require __DIR__.'/auth.php';

