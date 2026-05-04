<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'level' => fake()->randomElement(['Beginner', 'Intermediate', 'Advanced']),
            'icon' => '📚',
            'color' => 'from-indigo-500 to-violet-600',
            'estimated_hours' => fake()->numberBetween(5, 40),
            'is_published' => true,
            'order_index' => fake()->numberBetween(0, 10),
        ];
    }
}
