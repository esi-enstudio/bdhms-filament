<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HouseUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all user IDs
        $userIds = User::pluck('id')->toArray();

        // Get house IDs
        $houseIds = House::pluck('id')->toArray();

        foreach($userIds as $userId){
            // Randomly assign one house to the user
            $houseId = $houseIds[array_rand($houseIds)];

            DB::table('house_user')->insert([
                'user_id' => $userId,
                'house_id' => $houseId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
