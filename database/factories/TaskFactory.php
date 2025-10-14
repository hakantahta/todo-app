<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->boolean(60) ? $this->faker->paragraph() : null,
            'is_completed' => $this->faker->boolean(30),
            'due_at' => $this->faker->boolean(50) ? $this->faker->dateTimeBetween('-1 week', '+2 weeks') : null,
            'priority' => $this->faker->numberBetween(0, 5),
            'sort_order' => null,
        ];
    }
}
