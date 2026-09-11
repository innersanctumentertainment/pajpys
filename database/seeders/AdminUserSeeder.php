<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEEDER_ADMIN_PASSWORD', 'password');
        $admin = User::query()->updateOrCreate(['email' => 'admin@pajpys.com'], ['name' => 'Master Admin', 'password' => $password, 'email_verified' => true, 'email_verified_at' => now()]);
        $admin->syncRoles(['master_admin']);
    }
}
