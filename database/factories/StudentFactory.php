<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nia' => strval($this->faker->isbn10()),
            'name' => $this->faker->name(),
            'lastname1' => $this->faker->lastName(),
            'lastname2' => $this->faker->lastName()
        ];
    }
}
