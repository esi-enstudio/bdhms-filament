<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            ['name' => 'Emil Sadekin Islam', 'phone' => '01732547755', 'email' => 'nilemil007@gmail.com', 'status' => 'active', 'password' => Hash::make('32133213'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],

            ['name' => 'Zm', 'phone' => '01911000001', 'email' => 'zm@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],

            ['name' => 'Mdo', 'phone' => '01911000002', 'email' => 'mdo@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],

            ['name' => 'Manager 1', 'phone' => '01911000003', 'email' => 'manager1@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manager 2', 'phone' => '01911100003', 'email' => 'manager2@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manager 3', 'phone' => '01911110003', 'email' => 'manager3@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],

            ['name' => 'Supervisor 1', 'phone' => '01911000004', 'email' => 'supervisor1@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Supervisor 2', 'phone' => '01911000005', 'email' => 'supervisor2@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Supervisor 3', 'phone' => '01911000006', 'email' => 'supervisor3@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],

            ['name' => 'Rso 001', 'phone' => '01911000007', 'email' => 'rso001@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 955', 'phone' => '01911000008', 'email' => 'rso955@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 101', 'phone' => '01911000009', 'email' => 'rso101@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 102', 'phone' => '01911000010', 'email' => 'rso102@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 512', 'phone' => '01911000011', 'email' => 'rso512@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 363', 'phone' => '01911000012', 'email' => 'rso363@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 364', 'phone' => '01911000013', 'email' => 'rso364@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 474', 'phone' => '01911000014', 'email' => 'rso474@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 880', 'phone' => '01911000015', 'email' => 'rso880@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 003', 'phone' => '01911000016', 'email' => 'rso003@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 954', 'phone' => '01911000017', 'email' => 'rso954@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 103', 'phone' => '01911000018', 'email' => 'rso103@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 106', 'phone' => '01911000019', 'email' => 'rso106@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 196', 'phone' => '01911000020', 'email' => 'rso196@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 911', 'phone' => '01911000021', 'email' => 'rso911@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 912', 'phone' => '01911000022', 'email' => 'rso912@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 365', 'phone' => '01911000023', 'email' => 'rso365@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 366', 'phone' => '01911000024', 'email' => 'rso366@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 002', 'phone' => '01911000025', 'email' => 'rso002@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 104', 'phone' => '01911000026', 'email' => 'rso104@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 105', 'phone' => '01911000027', 'email' => 'rso105@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 950', 'phone' => '01911000028', 'email' => 'rso950@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 877', 'phone' => '01911000029', 'email' => 'rso877@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 878', 'phone' => '01911000030', 'email' => 'rso878@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 879', 'phone' => '01911000031', 'email' => 'rso879@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rso 26', 'phone' => '01911000059', 'email' => 'rso26@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],

            ['name' => 'Retailer 1', 'phone' => '01911000032', 'email' => 'retailer1@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 2', 'phone' => '01911000033', 'email' => 'retailer2@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 3', 'phone' => '01911000034', 'email' => 'retailer3@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 4', 'phone' => '01911000035', 'email' => 'retailer4@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 5', 'phone' => '01911000036', 'email' => 'retailer5@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 6', 'phone' => '01911000037', 'email' => 'retailer6@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 7', 'phone' => '01911000038', 'email' => 'retailer7@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 8', 'phone' => '01911000039', 'email' => 'retailer8@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 9', 'phone' => '01911000040', 'email' => 'retailer9@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 10', 'phone' => '01911000041', 'email' => 'retailer10@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 11', 'phone' => '01911000042', 'email' => 'retailer11@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 12', 'phone' => '01911000043', 'email' => 'retailer12@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 13', 'phone' => '01911000044', 'email' => 'retailer13@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 14', 'phone' => '01911000045', 'email' => 'retailer14@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 15', 'phone' => '01911000046', 'email' => 'retailer15@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 16', 'phone' => '01911000047', 'email' => 'retailer16@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 17', 'phone' => '01911000048', 'email' => 'retailer17@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 18', 'phone' => '01911000049', 'email' => 'retailer18@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 19', 'phone' => '01911000050', 'email' => 'retailer19@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 20', 'phone' => '01911000051', 'email' => 'retailer20@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 21', 'phone' => '01911000052', 'email' => 'retailer21@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 22', 'phone' => '01911000053', 'email' => 'retailer22@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 23', 'phone' => '01911000054', 'email' => 'retailer23@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 24', 'phone' => '01911000055', 'email' => 'retailer24@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 25', 'phone' => '01911000056', 'email' => 'retailer25@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 26', 'phone' => '01911000057', 'email' => 'retailer26@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retailer 27', 'phone' => '01911000058', 'email' => 'retailer27@gmail.com', 'status' => 'active', 'password' => Hash::make('password'), 'remember_token' => Str::random(10), 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
