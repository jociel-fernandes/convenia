<?php

namespace Database\Factories;

use App\Models\CollaboratorImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CollaboratorImport>
 */
class CollaboratorImportFactory extends Factory
{
    protected $model = CollaboratorImport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'filename' => $this->faker->uuid() . '.csv',
            'original_filename' => $this->faker->word() . '_colaboradores.csv',
            'status' => $this->faker->randomElement(['processing', 'completed', 'failed']),
            'total_rows' => $this->faker->numberBetween(1, 1000),
            'processed_rows' => function (array $attributes) {
                return $this->faker->numberBetween(0, $attributes['total_rows']);
            },
            'successful_rows' => function (array $attributes) {
                return $this->faker->numberBetween(0, $attributes['processed_rows']);
            },
            'failed_rows' => function (array $attributes) {
                return $attributes['processed_rows'] - $attributes['successful_rows'];
            },
            'errors' => function (array $attributes) {
                if ($attributes['failed_rows'] > 0) {
                    return [
                        'line_' . $this->faker->numberBetween(1, 10) => [
                            'email' => ['Email já está em uso']
                        ]
                    ];
                }
                return null;
            },
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => function (array $attributes) {
                if ($attributes['status'] === 'completed') {
                    return $this->faker->dateTimeBetween($attributes['started_at'], 'now');
                }
                return null;
            }
        ];
    }

    /**
     * Indicate that the import is processing.
     */
    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => 'processing',
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the import is completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the import failed.
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => null,
            'errors' => [
                'general' => 'Erro durante o processamento do arquivo'
            ]
        ]);
    }
}