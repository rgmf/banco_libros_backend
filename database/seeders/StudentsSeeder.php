<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cohort = Cohort::first();
        Student::factory()->count(5)->create([
            'cohort_id' => $cohort->id
        ]);
    }
}
