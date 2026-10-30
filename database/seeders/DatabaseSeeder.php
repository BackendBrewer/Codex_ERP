<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = env('SUPERADMIN_PASSWORD');
        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set SUPERADMIN_PASSWORD to at least 12 characters before seeding.');
        }

        User::query()->updateOrCreate(['email' => env('SUPERADMIN_EMAIL', 'superadmin@ustaad.pk')], [
            'name' => env('SUPERADMIN_NAME', 'MSZ Super Admin'),
            'role' => 'super_admin',
            'academy_id' => null,
            'password' => $password,
        ]);
    }
}
