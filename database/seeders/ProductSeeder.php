<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            ['name' => '', 'code' => 'SCMB-09', 'category' => 'SC', 'sub_category' => 'VOICE', 'face_value' => 9, 'lifting_price' => 8.81, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'MV10TK', 'category' => 'SC', 'sub_category' => 'MV', 'face_value' => 10, 'lifting_price' => 9.65, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCV-14', 'category' => 'SC', 'sub_category' => 'VOICE', 'face_value' => 14, 'lifting_price' => 13.5576, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCD-14', 'category' => 'SC', 'sub_category' => 'DATA', 'face_value' => 14, 'lifting_price' => 13.5576, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SC-19', 'category' => 'SC', 'sub_category' => 'VOICE', 'face_value' => 19, 'lifting_price' => 18.31, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCV-19-30M', 'category' => 'SC', 'sub_category' => 'VOICE', 'face_value' => 19, 'lifting_price' => 18.3092, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'MV20TK', 'category' => 'SC', 'sub_category' => 'VOICE', 'face_value' => 20, 'lifting_price' => 19.3, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCD-29-MB500', 'category' => 'SC', 'sub_category' => 'DATA', 'face_value' => 29, 'lifting_price' => 27.9125, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCV-29-40M', 'category' => 'SC', 'sub_category' => 'VOICE', 'face_value' => 29, 'lifting_price' => 27.9468, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCD-29-1GB-1-DAY', 'category' => 'SC', 'sub_category' => 'DATA', 'face_value' => 29, 'lifting_price' => 27.9125, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCD-49-1GB-3-DAY', 'category' => 'SC', 'sub_category' => 'DATA', 'face_value' => 49, 'lifting_price' => 47.1625, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SCD-69 Tk', 'category' => 'SC', 'sub_category' => 'DATA', 'face_value' => 69, 'lifting_price' => 66.4125, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'MV50TK', 'category' => 'SC', 'sub_category' => 'VOICE', 'face_value' => 50, 'lifting_price' => 48.2, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'MMST', 'category' => 'SIM', 'sub_category' => 'DESH ', 'face_value' => 0, 'lifting_price' => 341, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'ESIMP', 'category' => 'SIM', 'sub_category' => 'DESH ', 'face_value' => 0, 'lifting_price' => 341, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'MMSTS', 'category' => 'SIM', 'sub_category' => 'DUPLICATE ', 'face_value' => 0, 'lifting_price' => 341, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'ESIMUP', 'category' => 'SIM', 'sub_category' => 'DUPLICATE ', 'face_value' => 0, 'lifting_price' => 341, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'SIM SWAP', 'category' => 'SIM', 'sub_category' => 'SWAP ', 'face_value' => 0, 'lifting_price' => 323, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'ESIMSWAP', 'category' => 'SIM', 'sub_category' => 'SWAP ', 'face_value' => 0, 'lifting_price' => 332, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'EV-SWAP', 'category' => 'SIM', 'sub_category' => 'ESWAP ', 'face_value' => 0, 'lifting_price' => 100, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '', 'code' => 'Router', 'category' => 'WIFI', 'sub_category' => 'DEVICE ', 'face_value' => 0, 'lifting_price' => 2020, 'offer' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
