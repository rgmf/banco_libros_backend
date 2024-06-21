<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentLendingCollection;
use App\Http\Resources\BookCopyResource;
use App\Http\Resources\StudentResource;
use App\Http\Resources\ErrorResource;
use App\Models\Student;
use App\Models\BookCopy;

class LendingStatsController extends Controller
{
    public function listLendingByBookcopy(string $barcode)
    {
        $bookCopy = Bookcopy::with([
            'book',
            'book.grade',
            'lendings' => function ($query) {
                $query->orderBy('lending_date', 'desc');
            },
            'lendings.student',
            'lendings.academicYear',
            'lendings.lendingStatus',
            'lendings.returnedStatus'
        ])->where('barcode', $barcode)->first();

        if (!$bookCopy) {
            return new ErrorResource(404, "El libro con código de barras $barcode no existe");
        }

        return new BookCopyResource($bookCopy);
    }

    public function listLendingByStudent(string $studentId)
    {
        $student = Student::with([
            'lendings' => function ($query) {
                $query->orderBy('lending_date', 'desc');
            },
            'lendings.bookCopy',
            'lendings.academicYear',
            'lendings.lendingStatus',
            'lendings.returnedStatus'
        ])->find($studentId);

        if (!$student) {
            return new ErrorResource(404, "El estudiante identificado con el número $studentId no existe");
        }

        return new StudentResource($student);
    }

    public function listStudentsReturnByCohort(int $cohortId, int $academicYearId)
    {
        $students = Student::with('cohort')
            ->with(['lendings' => function($query) use ($academicYearId) {
                $query->whereNotNull('returned_date')
                    ->where('academic_year_id', $academicYearId);
            }])
            ->where('cohort_id', $cohortId)
            ->whereHas('lendings', function($query) use ($academicYearId) {
                $query->whereNotNull('returned_date')
                    ->where('academic_year_id', $academicYearId);
            })
            ->orderBy('lastname1')
            ->get();

        /*\Log::info($students->toSql());
          $students = $students->get();*/

        return new StudentLendingCollection($students);
    }
}
