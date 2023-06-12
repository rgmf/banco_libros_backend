<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicYear::create([
            'id' => 1,
            'name' => '2022-2023'
        ]);

        /*$academicYear = new AcademicYear();
        $academicYear->id = 123123123;
        $academicYear->name = '2022-2023';
        $academicYear->save();*/
        //AcademicYear::factory()->count(1)->create();
    }
}
