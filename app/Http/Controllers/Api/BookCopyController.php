<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CodebarGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookCopyRequest;
use App\Http\Resources\BookCopyCollection;
use App\Http\Resources\BookCopyResource;
use App\Http\Resources\ErrorResource;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Observation;
use App\Models\Status;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class BookCopyController extends Controller
{
    public function showByBarcode(int $barcode)
    {
        $bookCopy = BookCopy::with('status')
                  ->with('observations')
                  ->with('lendings')
                  ->where('barcode', $barcode)
                  ->first();
        if (!$bookCopy) {
            return new ErrorResource(404, 'La copia del libro que solicitas no existe');
        }
        return new BookCopyResource($bookCopy);
    }

    /**
     * Creates $count BookCopy from $bookId with status identified by $statusId.
     *
     * It returns the list of code bars (EAN13) generated.
     */
    public function store(int $bookId, int $count, int $statusId)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return new ErrorResource(404, 'El libro del que quieres crear copias no existe');
        }

        $status = Status::find($statusId);
        if (!$status) {
            return new ErrorResource(404, 'El estado indicado para los libros no existe');
        }

        $bookCopies = [];
        for ($i = 0; $i < $count; $i++) {
            do {
                $barcode13Str = CodebarGenerator::ean13();
            } while (BookCopy::where('barcode', $barcode13Str)->exists());

            $bookCopies[] = BookCopy::create([
                'barcode' => $barcode13Str,
                'book_id' => $bookId,
                'status_id' => $statusId
            ]);
        }

        return new BookCopyCollection($bookCopies);
    }

    public function update(BookCopyRequest $request, int $bookCopyId)
    {
        try {
            $bookCopy = BookCopy::findOrFail($bookCopyId);

            $bookCopy->fill($request->only('comment', 'status_id'));
            $bookCopy->save();

            $observationsIds = $request->input('observations', []);
            $observationModels = Observation::whereIn('id', $observationsIds)->get();

            $bookCopy->observations()->sync($observationModels);

            $bookCopy->load('observations');

            return new BookCopyResource($bookCopy, 201);
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return new ErrorResource(409, 'Ya existe una copia con esos datos', $exception);
            } else {
                return new ErrorResource(500, 'Error al actualizar la copia del libro', $exception);
            }
        } catch (ModelNotFoundException $exception) {
            return new ErrorResource(500, 'No existe la copia del libro indicada', $exception);
        }
    }
}
