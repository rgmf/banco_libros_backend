<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

use App\Models\BookCopy;
use App\Models\Lending;
use App\Models\Student;

class LendingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createCurrentLendingsFor1ESOA();

        /*$student = Student::first();
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
        ]);*/
    }

    private function createCurrentLendingsFor1ESOA(): void
    {
        $academicYear = AcademicYear::where('name', '2022-2023')->first();

        $bookCopies1 = BookCopy::orderBy('id')->whereHas('book', function($query) {
            $query->where('title', 'like', '%Castellano%');
        })->get();
        $bookCopies2 = BookCopy::orderBy('id')->whereHas('book', function($query) {
            $query->where('title', 'like', '%Matemáticas%');
        })->get();
        $bookCopies3 = BookCopy::orderBy('id')->whereHas('book', function($query) {
            $query->where('title', 'like', '%Historia%');
        })->get();
        $bookCopies4 = BookCopy::orderBy('id')->whereHas('book', function($query) {
            $query->where('title', 'like', '%Informática%');
        })->get();

        $students = Student::orderBy('id')->whereHas('cohort', function($query) {
            $query->where('name', '1ESOA');
        })->get();

        $lendingDate = now();

        $students->each(function($student, $i) use ($academicYear, $bookCopies1, $bookCopies2, $bookCopies3, $bookCopies4, $lendingDate) {
            if ($i >= $bookCopies1->count() || $i >= $bookCopies2->count() || $i >= $bookCopies3->count() || $i >= $bookCopies4->count()) {
                return false;
            }

            Lending::create([
                'student_id' => $student->id,
                'book_copy_id' => $bookCopies1[$i]->id,
                'academic_year_id' => $academicYear->id,
                'lending_date' => $lendingDate,
                'lending_status_id' => $bookCopies1[$i]->status->id
            ]);
            Lending::create([
                'student_id' => $student->id,
                'book_copy_id' => $bookCopies2[$i]->id,
                'academic_year_id' => $academicYear->id,
                'lending_date' => $lendingDate,
                'lending_status_id' => $bookCopies2[$i]->status->id
            ]);
            Lending::create([
                'student_id' => $student->id,
                'book_copy_id' => $bookCopies3[$i]->id,
                'academic_year_id' => $academicYear->id,
                'lending_date' => $lendingDate,
                'lending_status_id' => $bookCopies3[$i]->status->id
            ]);
            Lending::create([
                'student_id' => $student->id,
                'book_copy_id' => $bookCopies4[$i]->id,
                'academic_year_id' => $academicYear->id,
                'lending_date' => $lendingDate,
                'lending_status_id' => $bookCopies4[$i]->status->id
            ]);
        });
    }
}
