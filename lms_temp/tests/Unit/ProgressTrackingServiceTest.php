<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\UserProgress;
use App\Services\ProgressTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressTrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProgressTrackingService();
    }

    public function test_get_course_progress_with_no_lessons(): void
    {
        $user = \App\Models\User::factory()->create();
        $course = Course::factory()->create();

        $result = $this->service->getCourseProgress($user, $course);

        $this->assertSame(['total' => 0, 'completed' => 0, 'pct' => 0], $result);
    }

    public function test_get_course_progress_calculates_correctly(): void
    {
        $user = \App\Models\User::factory()->create();
        $course = Course::factory()->create();
        $chapter = \App\Models\Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = \App\Models\Lesson::factory()->count(5)->create(['chapter_id' => $chapter->id]);

        // Complete 2 of 5 lessons
        UserProgress::create(['user_id' => $user->id, 'lesson_id' => $lessons[0]->id, 'completed_at' => now()]);
        UserProgress::create(['user_id' => $user->id, 'lesson_id' => $lessons[1]->id, 'completed_at' => now()]);

        $result = $this->service->getCourseProgress($user, $course);

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(2, $result['completed']);
        $this->assertEquals(40, $result['pct']);
    }

    public function test_get_course_progress_does_not_count_incomplete(): void
    {
        $user = \App\Models\User::factory()->create();
        $course = Course::factory()->create();
        $chapter = \App\Models\Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = \App\Models\Lesson::factory()->create(['chapter_id' => $chapter->id]);

        // Access without completing
        UserProgress::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'completed_at' => null]);

        $result = $this->service->getCourseProgress($user, $course);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['completed']);
        $this->assertEquals(0, $result['pct']);
    }

    public function test_touch_lesson_creates_progress_row(): void
    {
        $user = \App\Models\User::factory()->create();
        $course = Course::factory()->create();
        $chapter = \App\Models\Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = \App\Models\Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $this->service->touchLesson($user, $lesson);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_touch_lesson_does_not_mark_complete(): void
    {
        $user = \App\Models\User::factory()->create();
        $course = Course::factory()->create();
        $chapter = \App\Models\Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = \App\Models\Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $this->service->touchLesson($user, $lesson);

        $this->assertNull(UserProgress::first()->completed_at);
    }
}
