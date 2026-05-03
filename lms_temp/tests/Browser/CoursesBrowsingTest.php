<?php

namespace Tests\Browser;

use App\Models\Course;
use App\Models\Chapter;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CoursesBrowsingTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * A user can see the courses listing page.
     */
    public function test_user_can_see_courses_list(): void
    {
        $course = Course::factory()->create([
            'title' => 'C Programming',
            'slug' => 'c-programming',
            'is_published' => true,
        ]);

        $this->browse(function (Browser $browser) use ($course) {
            $browser->visit('/courses')
                ->assertSee('C Programming')
                ->assertSee('Start Learning');
        });
    }

    /**
     * A user can navigate from the courses list to a course detail page.
     */
    public function test_user_can_click_into_course(): void
    {
        $course = Course::factory()->create([
            'title' => 'Web Development',
            'slug' => 'web-development',
            'is_published' => true,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'title' => 'HTML & CSS',
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'title' => 'HTML Foundations',
            'slug' => 'web-html',
        ]);

        $this->browse(function (Browser $browser) use ($course) {
            $browser->visit('/courses')
                ->clickLink('Web Development')
                ->assertPathIs("/courses/{$course->slug}")
                ->assertSee('HTML & CSS')
                ->assertSee('HTML Foundations');
        });
    }

    /**
     * An authenticated user can see lesson details.
     */
    public function test_authenticated_user_can_view_lesson(): void
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'learner@test.com',
            'password' => bcrypt('password'),
        ]);

        $course = Course::factory()->create(['slug' => 'test-course', 'title' => 'Test Course']);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'title' => 'Chapter 1']);
        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'title' => 'First Lesson',
            'slug' => 'first-lesson',
            'content_html' => '<p>Welcome to the first lesson!</p>',
        ]);

        $this->browse(function (Browser $browser) use ($user, $course, $lesson) {
            $browser->loginAs($user)
                ->visit("/courses/{$course->slug}/lessons/{$lesson->slug}")
                ->assertSee('First Lesson')
                ->assertSee('Welcome to the first lesson!')
                ->assertSee('Mark as Complete');
        });
    }
}
