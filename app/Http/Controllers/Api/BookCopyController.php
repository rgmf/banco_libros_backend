<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CodebarGenerator;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookCopyCollection;
use App\Http\Resources\ErrorResource;
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
}
