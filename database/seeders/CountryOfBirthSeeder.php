<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryOfBirth;
use Illuminate\Support\Facades\DB;

class CountryOfBirthSeeder extends Seeder
{
    public function run(): void
    {

        $rows = CountryOfBirth::factory()->count(10)->make()->toArray();  
        DB::table('country_of_birth')->insert($rows); 
    }
}
