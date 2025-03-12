<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\ItopReplace;
use App\Models\Product;
use App\Models\Retailer;
use App\Models\Rso;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            HouseSeeder::class,
//            UserSeeder::class,
//            HouseUserSeeder::class,
//            RsoSeeder::class,
            // RetailerSeeder::class,
//            ProductSeeder::class,
        ]);

//        User::factory()->create([
//            'name' => 'Emil Sadekin Islam',
//            'email' => 'nilemil007@gmail.com',
//            'phone' => '01732547755',
//            'status' => 'active',
//            'password' => Hash::make('32133213'),
//        ]);

        // ItopReplace::factory(10)->create();
    }
}
