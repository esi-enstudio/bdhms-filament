<?php

namespace Database\Seeders;

use App\Models\House;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        House::insert([
            ['code' => 'MYMVAI01', 'name' => 'Patwary Telecom', 'cluster' => 'East', 'region' => 'Brahmanbaria', 'district' => 'Kishoreganj', 'thana' => 'Bhairab', 'email' => 'patwarytelecom@gmail.com', 'address' => 'Bhairab bazar, Bhairab, Kishoreganj.', 'proprietor_name' => 'Samsuzzaman Patwary', 'contact_number' => '01917747555', 'poc_name' => 'Khasruzzaman Khasru', 'poc_number' => '01918537111', 'lifting_date' => '2009-05-09', 'status' => 'active', 'remarks' => 'Something...'],

            ['code' => 'MYMVAI02', 'name' => 'Modina Store', 'cluster' => 'North East', 'region' => 'Mymensingh', 'district' => 'Kishoreganj', 'thana' => 'Mithamoin', 'email' => 'blmodinastore@gmail.com', 'address' => 'Bhairab bazar, Bhairab, Kishoreganj.', 'proprietor_name' => 'Samsuzzaman Patwary', 'contact_number' => '01917747556', 'poc_name' => 'Khasruzzaman Khasru', 'poc_number' => '01918537112', 'lifting_date' => '2009-05-09', 'status' => 'active', 'remarks' => 'Something...'],

            ['code' => 'MYMVAI03', 'name' => 'Sumaya Enterprise', 'cluster' => 'East', 'region' => 'Brahmanbaria', 'district' => 'Kishoreganj', 'thana' => 'Bajitpur', 'email' => 'blsumayaenterprise@gmail.com', 'address' => 'Bhairab bazar, Bhairab, Kishoreganj.', 'proprietor_name' => 'Samsuzzaman Patwary', 'contact_number' => '01917747557', 'poc_name' => 'Khasruzzaman Khasru', 'poc_number' => '01918537113', 'lifting_date' => '2009-05-09', 'status' => 'inactive', 'remarks' => 'Something...'],
        ]);
    }
}
