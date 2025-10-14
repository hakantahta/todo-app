<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Varsayılan bir kullanıcı oluştur
        $user = User::query()->firstOr(function () {
            return User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
        });

        // Task seeder'ı çalıştır
        $this->call(TaskSeeder::class);
    }
}
