<?php

namespace Database\Seeders;

use App\Models\Rso;
use Illuminate\Database\Seeder;

class RsoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Rso::insert([
            ['house_id' => 1, 'user_id' => 7, 'rso_code' => 'RS040001', 'itop_number' => '01409944001', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 8, 'rso_code' => 'RS040002', 'itop_number' => '01908441955', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 9, 'rso_code' => 'RS040003', 'itop_number' => '01915270101', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 10, 'rso_code' => 'RS040004', 'itop_number' => '01915270102', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 11, 'rso_code' => 'RS040005', 'itop_number' => '01937600512', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 12, 'rso_code' => 'RS040006', 'itop_number' => '01984220363', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 13, 'rso_code' => 'RS040007', 'itop_number' => '01984220364', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 14, 'rso_code' => 'RS040008', 'itop_number' => '01986646474', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 15, 'rso_code' => 'RS040009', 'itop_number' => '01986686880', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 16, 'rso_code' => 'RS040010', 'itop_number' => '01409944003', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 17, 'rso_code' => 'RS0400011', 'itop_number' => '01908441954', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 18, 'rso_code' => 'RS0400012', 'itop_number' => '01915270103', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 19, 'rso_code' => 'RS0400013', 'itop_number' => '01915270106', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 20, 'rso_code' => 'RS0400014', 'itop_number' => '01915300196', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 21, 'rso_code' => 'RS0400015', 'itop_number' => '01984217911', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 22, 'rso_code' => 'RS0400016', 'itop_number' => '01984217912', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 23, 'rso_code' => 'RS0400017', 'itop_number' => '01984220365', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 24, 'rso_code' => 'RS0400018', 'itop_number' => '01984220366', 'status' => 'active'],
            ['house_id' => 2, 'user_id' => 25, 'rso_code' => 'RS0400019', 'itop_number' => '01409944002', 'status' => 'active'],
            ['house_id' => 2, 'user_id' => 26, 'rso_code' => 'RS040020', 'itop_number' => '01915270104', 'status' => 'active'],
            ['house_id' => 2, 'user_id' => 27, 'rso_code' => 'RS040021', 'itop_number' => '01915270105', 'status' => 'active'],
            ['house_id' => 2, 'user_id' => 28, 'rso_code' => 'RS040022', 'itop_number' => '01967042950', 'status' => 'active'],
            ['house_id' => 2, 'user_id' => 29, 'rso_code' => 'RS040023', 'itop_number' => '01986686877', 'status' => 'active'],
            ['house_id' => 2, 'user_id' => 30, 'rso_code' => 'RS040024', 'itop_number' => '01986686878', 'status' => 'active'],
            ['house_id' => 2, 'user_id' => 31, 'rso_code' => 'RS040025', 'itop_number' => '01986686879', 'status' => 'active'],
            ['house_id' => 1, 'user_id' => 32, 'rso_code' => 'RS040026', 'itop_number' => '01953610129', 'status' => 'active'],
        ]);
    }
}
