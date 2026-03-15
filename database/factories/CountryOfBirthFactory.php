<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CountryOfBirth>
 */
class CountryOfBirthFactory extends Factory
{
    public function definition(): array
    {
        return [  
            'country' => $this->faker->country()
        ];
    }
}
