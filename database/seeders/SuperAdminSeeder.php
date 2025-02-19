<?php

namespace Database\Seeders;

use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the Super Admin role
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // Create the Other's role
        Role::insert([
            [
                'name' => 'manager',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'supervisor',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'rso',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'retailer',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create a Super Admin user
        $user = User::firstOrCreate([
            'email' => 'nilemil007@gmail.com',
        ], [
            'name' => 'Emil Sadekin Islam',
            'phone' => '01732547755',
            'password' => Hash::make('3213'), // Change this to a secure password
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign the Super Admin role to the user
        $user->assignRole($superAdminRole);
    }
}
