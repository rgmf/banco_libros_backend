<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lending;
use App\Http\Requests\LendingRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\LendingCollection;
use App\Models\BookCopy;
use Illuminate\Support\Facades\DB;

class LendingController extends Controller
{
    public function index()
    {
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

                $lendingItem->loadMissing(['student', 'bookCopy', 'academicYear']);

                $lending[] = $lendingItem;
            }

            DB::commit();

            return new LendingCollection($lending);
        } catch (\Exception $e) {
            DB::rollback();
            return new ErrorResource(500, 'Error en la transacción de creación de un préstamo: ' . $e->getMessage());
        }
    }

    public function show(Lending $lending)
    {
    }

    public function update(LendingRequest $request, Lending $lending)
    {
    }

    public function destroy(Lending $lending)
    {
    }
}
