<?php

namespace Database\Factories;

use App\Models\Bien;
use App\Models\Statut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bien>
 */
class BienFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Bien::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titre' => fake()->sentence(3),
            'surface' => fake()->numberBetween(20, 300),
            'description' => fake()->paragraph(3),
            'pieces' => fake()->numberBetween(1, 8),
            'chambres' => fake()->numberBetween(0, 5),
            'etage' => fake()->numberBetween(0, 15),
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->city(),
            'codePostal' => fake()->postcode(),
            'statut_id' => Statut::query()->inRandomOrder()->value('id'),
            'prix' => fake()->numberBetween(50000, 1000000),
        ];
    }
}
