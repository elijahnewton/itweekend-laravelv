<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'chapter_id' => Chapter::factory(),
            'title' => ucwords($title),
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 9999),
            'content_html' => '<p>' . fake()->paragraph() . '</p>',
            'code_example' => 'echo "Hello World";',
            'code_language' => 'php',
            'order_index' => fake()->numberBetween(0, 10),
            'estimated_minutes' => fake()->numberBetween(5, 60),
        ];
    }
}
