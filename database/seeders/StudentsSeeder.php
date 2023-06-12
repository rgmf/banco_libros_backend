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
        $cohort = Cohort::where('name', '1ESOA')->first();

        Student::create([
            'nia' => '11111111',
            'name' => 'María',
            'lastname1' => 'Martínez',
            'lastname2' => 'Marín',
            'cohort_id' => $cohort->id
        ]);

        Student::create([
            'nia' => '22222222',
            'name' => 'Pepa',
            'lastname1' => 'Pérez',
            'lastname2' => 'Parra',
            'cohort_id' => $cohort->id
        ]);

        Student::create([
            'nia' => '33333333',
            'name' => 'Sofía',
            'lastname1' => 'Saez',
            'lastname2' => 'Sainz',
            'cohort_id' => $cohort->id
        ]);

        Student::create([
            'nia' => '44444444',
            'name' => 'Jose',
            'lastname1' => 'Jiménez',
            'lastname2' => 'Jaén',
            'cohort_id' => $cohort->id
        ]);

        Student::create([
            'nia' => '55555555',
            'name' => 'Pepe',
            'lastname1' => 'Palomares',
            'lastname2' => 'Pérez',
            'cohort_id' => $cohort->id
        ]);

        /*$cohort = Cohort::first();
        Student::factory()->count(5)->create([
            'cohort_id' => $cohort->id
        ]);*/
    }
}
