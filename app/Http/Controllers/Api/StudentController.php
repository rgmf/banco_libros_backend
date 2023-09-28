<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStudentsRequest;
use App\Http\Requests\CohortsMessagingRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\MessagesResource;
use App\Http\Resources\StudentCollection;
use App\Http\Resources\StudentResource;
use App\Jobs\SendOpenTextEmailJob;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('cohort')->with('lendings.bookCopy')->get();
        return new StudentCollection($students);
    }

    public function show(int $id)
    {
        $student = Student::with('cohort')->with('lendings.bookCopy')->find($id);
        if (!$student) {
            return new ErrorResource(404, 'El/la estudiante que solicitas no existe');
        }
        return new StudentResource($student);
    }

    public function storeBulk(BulkStudentsRequest $request)
    {
        $students = $request->input('students');
        $studentsInserted = [];

        foreach ($students as $studentData) {
            $existingStudent = Student::where('nia', $studentData['nia'])->first();

            if ($existingStudent) {
                $existingStudent->update($studentData);
                $existingStudent->load('cohort');
                $studentsInserted[] = $existingStudent;
            } else {
                $newStudent = Student::create($studentData);
                $newStudent->load('cohort');
                $studentsInserted[] = $newStudent;
            }
        }

        return new StudentCollection($studentsInserted);

        /*$students = $request->input('students');
        $studentsInserted = [];

        foreach ($students as $student) {
            try {
                $student = Student::create($student);
                $student->load('cohort');
                $studentsInserted[] = $student;
            } catch (QueryException $exception) {
            }
        }

        return new StudentCollection($studentsInserted);*/
    }

    public function cohortsMessaging(CohortsMessagingRequest $request)
    {
        $text = $request->input('text');
        $cohortIds = $request->input('cohorts');
        $numberOfMessages = 0;
        foreach ($cohortIds as $cohortId) {
            $students = Student::where('cohort_id', $cohortId)->where('is_member', true)->get();
            foreach ($students as $student) {
                $this->dispatchLendingEmail($student, $text);
                $numberOfMessages++;
            }
        }

        return new MessagesResource(strval($numberOfMessages));
    }

    private function dispatchLendingEmail(Student $student, string $text) {
        try {
            dispatch(new SendOpenTextEmailJob($student, $text));
        } catch (\Exception $e) {
            // Registra la excepción en los logs u toma medidas según sea necesario
            $studentId = $student->id;
            Log::error("Error al enviar el correo electrónico al estudiante identificado con $studentId: " . $e->getMessage());
        }
    }
}
