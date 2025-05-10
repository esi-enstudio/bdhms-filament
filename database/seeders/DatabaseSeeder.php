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
            HouseSeeder::class,
//            SuperAdminSeeder::class,
//            UserSeeder::class,
//            HouseUserSeeder::class,
//            RsoSeeder::class,
            // RetailerSeeder::class,
//            ProductSeeder::class,
        ]);

        // ItopReplace::factory(10)->create();
    }
}
