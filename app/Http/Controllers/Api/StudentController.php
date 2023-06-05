<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStudentsRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\StudentCollection;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Database\QueryException;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('cohort')->with('lendings')->get();
        return new StudentCollection($students);
    }

    public function show(int $id)
    {
        $student = Student::with('cohort')->find($id);
        if (!$student) {
            return new ErrorResource(404, 'El/la estudiante que solicitas no existe');
        }
        return new StudentResource($student);
    }

    public function storeBulk(BulkStudentsRequest $request)
    {
        $students = $request->input('students');
        $studentsInserted = [];

        foreach ($students as $student) {
            try {
                $student = Student::create($student);
                $student->load('cohort');
                $studentsInserted[] = $student;
            } catch (QueryException $exception) {
            }
        }

        return new StudentCollection($studentsInserted);
    }
}
