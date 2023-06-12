<?php

namespace Database\Seeders;

use App\Models\Cohort;
use Illuminate\Database\Seeder;

class CohortsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cohort = Cohort::create([
            'id' => 1,
            'name' => '1ESOA'
        ]);
        //Cohort::factory()->count(1)->create();
    }
}
