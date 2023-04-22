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
        $cohort = new Cohort();
        $cohort->id = 123123123;
        $cohort->name = '1INVENTADO';
        $cohort->save();
        //Cohort::factory()->count(1)->create();
    }
}
