<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lending;
use App\Http\Requests\LendingRequest;
use App\Http\Requests\LendingUpdateRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\LendingCollection;
use App\Http\Resources\LendingResource;
use App\Models\BookCopy;
use Illuminate\Support\Facades\DB;

class LendingController extends Controller
{
    public function index()
    {
    }

    public function indexByStudent(int $studentId)
    {
        $lendings = Lending::with('student')
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

    public function indexByBookBarcode(int $barcode)
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

    public function showByBookBarcode(int $barcode)
    {
        $lendings = Lending::with('student')
                  ->with('bookCopy.book')
                  ->with('bookCopy.status')
                  ->with('bookCopy.observations')
                  ->with('academicYear')
                  ->whereHas('bookCopy', function ($query) use ($barcode) {
                      $query->where('barcode', $barcode);
                  })
                  ->first();
        if ($lendings->count() == 0) {
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

            foreach ($bookCopies as $bookCopyData) {
                $bookCopy = BookCopy::findOrFail($bookCopyData['id']);
                $bookCopy->status_id = $bookCopyData['status_id'];
                $bookCopy->observations()->sync(array_key_exists('observations_id', $bookCopyData) ? $bookCopyData['observations_id'] : []);
                $bookCopy->save();

                $lendingItem = new Lending();
                $lendingItem->fill([
                    'student_id' => $request->input('student_id'),
                    'book_copy_id' => $bookCopy->id,
                    'academic_year_id' => $request->input('academic_year_id'),
                    'lending_date' => now(),
                    'lending_status_id' => $bookCopyData['status_id'],
                ]);
                $lendingItem->save();

                $lendingItem->loadMissing(['student', 'bookCopy', 'bookCopy.observations', 'bookCopy.status', 'academicYear']);

                $lending[] = $lendingItem;
            }

            DB::commit();

            return new LendingCollection($lending);
        } catch (\Exception $e) {
            DB::rollback();
            return new ErrorResource(500, 'Error en la transacción de creación de un préstamo: ' . $e->getMessage());
        }
    }

    public function update(LendingUpdateRequest $request, Lending $lending)
    {
        try {
            $lending->returned_status_id = $request->input('returned_status_id');
            $lending->returned_date = now();
            $lending->save();
            return new LendingResource($lending, 201);
        } catch (\Exception $e) {
            return new ErrorResource(500, 'Error al intentar modificar el préstamos');
        }
    }
}
