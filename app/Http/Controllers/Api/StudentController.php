<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStudentsRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\StudentCollection;
use App\Models\Student;
use Illuminate\Database\QueryException;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::get();
        return new StudentCollection($students);
    }

    public function storeBulk(BulkStudentsRequest $request)
    {
        try {
            $students = $request->input('students');
            Student::insert($students);
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return new ErrorResource(409, 'Hay estudiantes que ya existen', $exception);
            } else {
                return new ErrorResource(500, 'Error al insertar los estudiantes', $exception);
            }
        }

        return new StudentCollection($students);
    }
}
