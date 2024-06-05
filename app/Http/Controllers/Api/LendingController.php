<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lending;
use App\Http\Requests\LendingRequest;
use App\Http\Requests\LendingReturnRequest;
use App\Http\Requests\LendingUpdateRequest;
use App\Http\Requests\LendingGradesMessagingRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\InfoResource;
use App\Http\Resources\LendingCollection;
use App\Http\Resources\LendingResource;
use App\Http\Resources\MessagesResource;
use App\Models\BookCopy;
use App\Models\Observation;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;

class LendingController extends Controller
{
    private function buildObservationsSummary(array $observationsId)
    {
        $observations = Observation::find($observationsId);

        return array_reduce($observations->toArray(), function ($carry, $observation) {
            return $carry . ($carry !== '' ? "\n" : '') . $observation['title'];
        }, '');
    }

    public function index()
    {
    }

    public function indexByStudent(int $studentId)
    {
        $lendings = Lending::with('student.cohort')
                  ->with('bookCopy.book')
                  ->with('bookCopy.status')
                  ->with('bookCopy.observations')
                  ->with('academicYear')
                  ->where('student_id', $studentId)
                  ->where('returned_date', null)
                  ->get();
        if ($lendings->count() == 0) {
            return new ErrorResource(404, 'No existe préstamo para el estudiante dado');
        }
        return new LendingCollection($lendings);
    }

    public function indexByBookBarcode(string $barcode)
    {
        $lendings = Lending::select('lendings.*')
                  ->join('students', 'lendings.student_id', '=', 'students.id')
                  ->where('returned_date', null)
                  ->whereIn('students.id', function ($query) use ($barcode) {
                      $query->select('student_id')
                            ->from('lendings')
                            ->whereIn('book_copy_id', function ($query) use ($barcode) {
                                $query->select('id')
                                      ->from('book_copies')
                                      ->where('barcode', $barcode);
                            });
                  })
                  ->get();
        if ($lendings->count() == 0) {
            return new ErrorResource(404, 'No existe préstamo en el que esté el libro con el código de barras dado');
        }
        return new LendingCollection($lendings);
    }

    public function showByBookBarcode(string $barcode)
    {
        $lendings = Lending::with('student')
                  ->with('bookCopy.book')
                  ->with('bookCopy.status')
                  ->with('bookCopy.observations')
                  ->with('academicYear')
                  ->where('returned_date', null)
                  ->whereHas('bookCopy', function ($query) use ($barcode) {
                      $query->where('barcode', $barcode);
                  })
                  ->first();
        if (!$lendings || $lendings->count() == 0) {
            return new ErrorResource(404, 'No existe préstamo en el que esté el libro con el código de barras dado');
        }
        return new LendingResource($lendings);
    }

    public function store(LendingRequest $request)
    {
        try {
            DB::reconnect();
            DB::beginTransaction();

            $bookCopies = $request->input('book_copies');
            $lending = [];

            $academicYearId = $request->input('academic_year_id');
            $studentId = $request->input('student_id');

            // Students with other years lendings cannot receive new lendings this academic year
            $activeLendingsOtherYears = Lending::where('student_id', $studentId)
                ->where('academic_year_id', '<>', $academicYearId)
                ->whereNull('returned_date')
                ->first();
            if ($activeLendingsOtherYears) {
                return new ErrorResource(500, 'El estudiante tiene préstamos abiertos de otros cursos académicos');
            }

            foreach ($bookCopies as $bookCopyData) {
                // Book already lended and not returned?
                $existingLending = Lending::where('book_copy_id', $bookCopyData['id'])
                    ->whereNull('returned_date')
                    ->first();

                if ($existingLending) {
                    DB::rollBack();
                    return new ErrorResource(409, 'El libro ya está prestado');
                }

                $bookCopy = BookCopy::findOrFail($bookCopyData['id']);
                $bookCopy->status_id = $bookCopyData['status_id'];
                if (array_key_exists('comment', $bookCopyData)) {
                    $bookCopy->comment = $bookCopyData['comment'];
                }
                $bookCopy->observations()->sync(array_key_exists('observations_id', $bookCopyData) ? $bookCopyData['observations_id'] : []);
                $bookCopy->save();

                $lendingItem = new Lending();
                $lendingItem->fill([
                    'student_id' => $studentId,
                    'book_copy_id' => $bookCopy->id,
                    'academic_year_id' => $academicYearId,
                    'lending_date' => now(),
                    'lending_status_id' => $bookCopyData['status_id'],
                ]);
                $lendingItem->save();

                $lendingItem->loadMissing(['student', 'bookCopy', 'bookCopy.observations', 'bookCopy.status', 'academicYear']);

                $lending[] = $lendingItem;
            }

            DB::commit();

            $this->dispatchLendingEmail($studentId, $academicYearId);

            return new LendingCollection($lending);
        } catch (QueryException $e) {
            DB::rollBack();
            $error_code = $e->errorInfo[1];
            if ($error_code == 1062) {
                return new ErrorResource(409, 'El libro ya está prestado', $e);
            } else {
                return new ErrorResource(500, 'Error al prestar el libro', $e);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return new ErrorResource(500, 'Error en la transacción de creación de un préstamo: ' . $e->getMessage());
        }
    }

    public function update(LendingUpdateRequest $request, Lending $lending)
    {
        try {
            DB::reconnect();
            DB::beginTransaction();

            $statusId = $request->input('returned_status_id');
            $observationsId = $request->has('observations_id') ? $request->input('observations_id') : [];
            $comment = $request->has('comment') ? $request->input('comment') : '';
            $observationsSummary = $this->buildObservationsSummary($observationsId);

            $lending->returned_status_id = $statusId;
            $lending->returned_date = now();
            $lending->returned_comment = strlen($observationsSummary) > 0 && strlen($comment) > 0 ? $observationsSummary . "\n" . $comment : $observationsSummary . $comment;
            $lending->save();

            $bookCopy = BookCopy::findOrFail($lending->bookCopy->id);
            $bookCopy->status_id = $statusId;
            $bookCopy->observations()->sync($observationsId);
            $bookCopy->comment = $comment;
            $bookCopy->save();

            DB::commit();

            return new LendingResource($lending, 201);
        } catch (\Exception $e) {
            DB::rollback();
            return new ErrorResource(500, 'Error al intentar modificar el préstamos', $e);
        }
    }

    public function destroy($id)
    {
        try {
            $lending = Lending::findOrFail($id);
            $lending->delete();
            return new InfoResource(200, 'El préstamo ha sido eliminado correctamente');
        } catch (\Exception $e) {
            return new ErrorResource(500, 'Error al eliminar el préstamo: ' . $e->getMessage());
        }
    }

    public function return(LendingReturnRequest $request)
    {
        try {
            DB::reconnect();
            DB::beginTransaction();

            $result = [];

            $studentId = $request->input('student_id');
            $bookCopies = $request->input('book_copies');

            foreach ($bookCopies as $bookCopyData) {
                $lendings = Lending::where('student_id', $studentId)
                    ->where('book_copy_id', $bookCopyData['id'])
                    ->whereNull('returned_date')
                    ->get();

                if ($lendings->count() != 1) {
                    DB::rollBack();
                    $msg = "Se esperaba 1 préstamo para student_id=$studentId y book_copy_id={$bookCopyData['id']} pero se han obtenido {$lendings->count()} préstamos";
                    if ($lendings->count() == 0) {
                        $msg .= ", así pues, el estudiante no tiene prestado dicho libro";
                    }
                    return new ErrorResource(500, $msg);
                }

                // Update lending
                $lending = $lendings->first();
                $lending->fill([
                    'returned_date' => now(),
                    'returned_status_id' => $bookCopyData['status_id'],
                ]);
                $lending->save();

                // Update new status of the book copy (observations and comment)
                $bookCopy = BookCopy::find($bookCopyData['id']);
                if (!$bookCopy) {
                    DB::rollback();
                    return new ErrorResource(404, "No existe la copia del libro con id={$bookCopyData['id']}");
                }

                if (array_key_exists('comment', $bookCopyData)) {
                    $bookCopy->comment = $bookCopyData['comment'];
                }
                $bookCopy->observations()->sync(array_key_exists('observations_id', $bookCopyData) ? $bookCopyData['observations_id'] : []);
                $bookCopy->save();

                // Preparing data to return
                $lending->loadMissing(['student', 'bookCopy', 'bookCopy.observations', 'bookCopy.status', 'academicYear']);

                $result[] = $lending;
            }

            DB::commit();

            return new LendingCollection($result);
        } catch (QueryException $e) {
            DB::rollback();
        } catch (\Exception $e) {
            DB::rollback();
        }
    }

    public function gradesMessaging(LendingGradesMessagingRequest $request)
    {
        $gradeIds = $request->input('grades');
        $numberOfMessages = 0;
        foreach ($gradeIds as $gradeId) {
            $results = Lending::select('students.id', 'lendings.academic_year_id')
                ->join('students', 'lendings.student_id', '=', 'students.id')
                ->join('book_copies', 'lendings.book_copy_id', '=', 'book_copies.id')
                ->join('books', 'book_copies.book_id', '=', 'books.id')
                ->where('books.grade_id', $gradeId)
                ->where('lendings.returned_date', null)
                ->distinct()
                ->get();
            foreach ($results as $result) {
                $this->dispatchLendingEmail($result["id"], $result["academic_year_id"]);
                $numberOfMessages++;
            }
        }

        return new MessagesResource(strval($numberOfMessages));
    }

    private function dispatchLendingEmail($studentId, $academicYearId) {
        try {
            dispatch(new SendEmailJob($studentId, $academicYearId));
        } catch (\Exception $e) {
            // Registra la excepción en los logs u toma medidas según sea necesario
            Log::error("Error al enviar el correo electrónico al estudiante identificado con $studentId: " . $e->getMessage());
        }
        /*$student = Student::find($studentId);
        if (!$student->email_mother && !$student->email_father) {
            Mail::to('bancodelibros@ieslaencanta.com')
                ->send(new LendingErrorMail(
                    $studentId,
                    $academicYearId,
                    "No tenemos información de e-mails de este estudiante"
                ));
            return;
        }

        $recipients = [];

        if ($student->email_mother) {
            if ($student->email_mother !== "roman@letero.es") {
                return;
            }
            $recipients[] = $student->email_mother;
        }

        if ($student->email_father) {
            if ($student->email_father !== "rmartinezf@ieslaencanta.com") {
                return;
            }
            $recipients[] = $student->email_father;
        }

        Mail::to($recipients)
            ->bcc('bancodelibros@ieslaencanta.com')
            ->send(
                new LendingMail($studentId, $academicYearId)
            );*/
    }
}
