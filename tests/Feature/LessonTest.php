<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    private function createCourseWithLesson(): array
    {
        $course = Course::factory()->create(['slug' => 'test-course']);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'slug' => 'test-lesson']);

        return [$course, $chapter, $lesson];
    }

    public function test_lesson_show_redirects_guests_to_login(): void
    {
        [$course, , $lesson] = $this->createCourseWithLesson();

        $response = $this->get("/courses/{$course->slug}/lessons/{$lesson->slug}");

        $response->assertRedirect('/login');
    }

    public function test_lesson_show_returns_ok_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        [$course, , $lesson] = $this->createCourseWithLesson();

        $response = $this->actingAs($user)
            ->get("/courses/{$course->slug}/lessons/{$lesson->slug}");

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Lessons/Show')
                ->has('course')
                ->has('lesson')
                ->has('completedLessonIds')
        );
    }

    public function test_lesson_show_touches_progress(): void
    {
        $user = User::factory()->create();
        [$course, , $lesson] = $this->createCourseWithLesson();

        $this->actingAs($user)
            ->get("/courses/{$course->slug}/lessons/{$lesson->slug}");

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_mark_complete_requires_auth(): void
    {
        [$course, , $lesson] = $this->createCourseWithLesson();

        $response = $this->postJson('/progress/complete', ['lesson_id' => $lesson->id]);

        $response->assertUnauthorized();
    }

    public function test_mark_complete_returns_success(): void
    {
        $user = User::factory()->create();
        [$course, , $lesson] = $this->createCourseWithLesson();

        $response = $this->actingAs($user)
            ->postJson('/progress/complete', ['lesson_id' => $lesson->id]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Lesson marked as complete.');

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertNotNull(
            \App\Models\UserProgress::where(['user_id' => $user->id, 'lesson_id' => $lesson->id])
                ->first()->completed_at
        );
    }

    public function test_mark_complete_validates_lesson_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/progress/complete', ['lesson_id' => 99999]);

        $response->assertUnprocessable();
    }
}
