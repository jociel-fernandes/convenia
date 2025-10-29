<?php

namespace Database\Factories;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collaborator>
 */
class CollaboratorFactory extends Factory
{
    protected $model = Collaborator::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cpf' => $this->generateValidCpf(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Generate a valid CPF for testing purposes
     */
    private function generateValidCpf(): string
    {
        // Generate first 9 digits
        $cpf = '';
        for ($i = 0; $i < 9; $i++) {
            $cpf .= rand(0, 9);
        }

        // Calculate first verification digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
        $cpf .= $digit1;

        // Calculate second verification digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
        $cpf .= $digit2;

        return $cpf;
    }
}