<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

use App\Models\BookCopy;
use App\Models\Lending;
use App\Models\Status;
use App\Models\Student;

class LendingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student = Student::first();
        $bookCopy = BookCopy::first();
        $academicYear = AcademicYear::first();
        $lendingDate = now();
        $status = Status::first();

        $lending = Lending::create([
            'student_id' => $student->id,
            'book_copy_id' => $bookCopy->id,
            'academic_year_id' => $academicYear->id,
            'lending_date' => $lendingDate,
            'lending_status_id' => $status->id
        ]);
    }
}
