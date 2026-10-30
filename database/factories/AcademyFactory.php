<?php

namespace Database\Factories;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Academy>
 */
class AcademyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Academy';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => '03'.fake()->numerify('#########'),
            'city' => fake()->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Peshawar', 'Quetta']),
            'province' => fake()->randomElement(['Punjab', 'Sindh', 'Islamabad Capital Territory', 'Khyber Pakhtunkhwa', 'Balochistan']),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
