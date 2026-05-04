<?php

namespace Tests\Feature;

use App\Events\LessonCompleted;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\ProgressTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_complete_creates_progress_record(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $service = new ProgressTrackingService();
        $progress = $service->markComplete($user, $lesson, 90);

        $this->assertNotNull($progress->completed_at);
        $this->assertEquals(90, $progress->score);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'score' => 90,
        ]);

        Event::assertDispatched(LessonCompleted::class, fn ($e) =>
            $e->user->id === $user->id && $e->lesson->id === $lesson->id
        );
    }

    public function test_mark_complete_idempotent_on_second_call(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $service = new ProgressTrackingService();
        $service->markComplete($user, $lesson, 70);
        $service->markComplete($user, $lesson, 85);

        $this->assertDatabaseCount('user_progress', 1);
        $this->assertEquals(85, UserProgress::first()->score);
    }

    public function test_get_course_progress_returns_correct_percentage(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(4)->create(['chapter_id' => $chapter->id]);

        // Mark 3 of 4 lessons complete
        foreach ($lessons->take(3) as $lesson) {
            UserProgress::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'completed_at' => now(),
            ]);
        }

        $service = new ProgressTrackingService();
        $result = $service->getCourseProgress($user, $course);

        $this->assertEquals(4, $result['total']);
        $this->assertEquals(3, $result['completed']);
        $this->assertEquals(75, $result['pct']);
    }

    public function test_get_course_progress_returns_zero_for_empty_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $service = new ProgressTrackingService();
        $result = $service->getCourseProgress($user, $course);

        $this->assertEquals(['total' => 0, 'completed' => 0, 'pct' => 0], $result);
    }

    public function test_progress_complete_endpoint(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $response = $this->actingAs($user)
            ->postJson('/progress/complete', [
                'lesson_id' => $lesson->id,
                'score' => 100,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'score' => 100,
        ]);
    }
}
