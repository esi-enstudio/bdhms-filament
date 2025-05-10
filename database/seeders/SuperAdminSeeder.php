<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws Exception
     */
    public function run(): void
    {
        // প্রথম বিদ্যমান House লোড করুন
        $house = House::first();

        // যদি কোনো House না থাকে, তবে ত্রুটি দেখান
        if (!$house) {
            throw new Exception('No House found. Please run HouseSeeder first.');
        }

        // Create the Super Admin role
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'house_id' => $house->id,
        ]);

        // নিশ্চিত করুন যে রোলটি সঠিকভাবে তৈরি হয়েছে
        if (!$superAdminRole->exists) {
            throw new \Exception('Failed to create or find super_admin role.');
        }

        // অন্যান্য রোল তৈরি করুন
        $roles = [
            ['name' => 'manager', 'guard_name' => 'web', 'house_id' => $house->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'supervisor', 'guard_name' => 'web', 'house_id' => $house->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'rso', 'guard_name' => 'web', 'house_id' => $house->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'bp', 'guard_name' => 'web', 'house_id' => $house->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'accountant', 'guard_name' => 'web', 'house_id' => $house->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'retailer', 'guard_name' => 'web', 'house_id' => $house->id, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name'], 'house_id' => $roleData['house_id']],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Create a Super Admin user
        $user = User::firstOrCreate([
            'email' => 'emil@bdhms.com',
        ], [
            'name' => 'Emil Sadekin Islam',
            'phone' => '01732547755',
            'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'Pa$$w0rD')),
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ইউজারকে House এর সাথে সংযুক্ত করুন
        $user->houses()->syncWithoutDetaching([$house->id]);

        // Super Admin রোল অ্যাসাইন করুন, house_id সহ
        $user->assignRole($superAdminRole->name, ['house_id' => $house->id]);
    }
}
