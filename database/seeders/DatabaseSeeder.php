<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Model events stay on: models render
     * their Markdown and make their slugs while saving.
     */
    public function run(): void
    {
        $this->call(RealContentSeeder::class);

        if (app()->isLocal()) {
            $this->call(DemoSeeder::class);

            User::factory()->admin()->create([
                'name' => 'Kadir Gülec',
                'email' => 'admin@example.com',
            ]);
        }
    }
}
