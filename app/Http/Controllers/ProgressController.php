<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\ProgressTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function complete(Request $request, ProgressTrackingService $service): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $lesson = Lesson::findOrFail($validated['lesson_id']);
        $progress = $service->markComplete($request->user(), $lesson, $validated['score'] ?? null);

        return response()->json([
            'message' => 'Lesson marked as complete.',
            'progress' => $progress,
        ]);
    }
}
