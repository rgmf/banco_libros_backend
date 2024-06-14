<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentLendingCollection;
use App\Models\Student;

class LendingStatsController extends Controller
{
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
