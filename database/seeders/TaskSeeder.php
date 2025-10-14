<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        // Eğer mevcut kullanıcı yoksa bir tane oluştur
        $user = User::first() ?? User::factory()->create([
            'email' => 'demo@example.com',
        ]);

        // Kullanıcıya ait 15 görev oluştur
        Task::factory()
            ->count(15)
            ->for($user)
            ->create();
    }
}


