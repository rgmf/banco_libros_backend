<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CodebarGenerator;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Status;

class BookCopyController extends Controller
{
    /**
     * Creates $count BookCopy from $bookId with status identified by $statusId.
     *
     * It returns the list of code bars (EAN13) generated.
     */
    public function store(int $bookId, int $count, int $statusId)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return response()->json([
                'message' => 'El libro del que quieres crear copias no existe'
            ], 404);
        }

        $status = Status::find($statusId);
        if (!$status) {
            return response()->json([
                'message' => 'El estado indicado para los libros no existe'
            ], 404);
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

        return response()->json([
            'message' => 'Copias del libro creadas correctamente',
            'book_copies' => $bookCopies
        ], 200);
    }
}
