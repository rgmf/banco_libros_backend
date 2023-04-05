<?php
namespace App\Http\Controllers\Api;

use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::get();
        return $books;
    }

    public function store(BookRequest $request)
    {
        try {
            $book = Book::create([
                'isbn' => $request->input('isbn'),
                'title' => $request->input('title'),
                'author' => $request->input('author'),
                'publisher' => $request->input('publisher'),
                'volumes' => $request->input('volumes')
            ]);
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return response()->json([
                    'message' => 'El libro ya existe',
                    'error' => $exception->getMessage()
                ], 409);
            } else {
                return response()->json([
                    'message' => 'Error al insertar el libro',
                    'error' => $exception->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Libro insertado correctamente',
            'book' => $book
        ], 201);
    }

    public function show(int $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json([
                'message' => 'El libro que solicitas no existe'
            ], 404);
        }

        return response()->json([
            'book' => $book
        ], 200);
    }

    public function update(BookUpdateRequest $request, Book $book)
    {
        try {
            $book->fill($request->only($request->keys()));
            $book->save();
            return response()->json([
                'message' => 'Libro actualizado correctamente',
                'book' => $book
            ], 201);
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return response()->json([
                    'message' => 'Ya existe un libro con esos datos',
                    'error' => $exception->getMessage()
                ], 409);
            } else {
                return response()->json([
                    'message' => 'Error al actualizar el libro',
                    'error' => $exception->getMessage()
                ], 500);
            }
        }
    }

    public function destroy(int $id)
    {
        try {
            $book = Book::findOrFail($id);
            $book->delete();
            return response()->json(['message' => 'El libro ha sido eliminado correctamente'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'El libro que intenta eliminar no existe',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Se produjo un error al eliminar el libro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBookCopies(int $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json([
                'message' => 'El libro del que buscas copias no existe'
            ], 404);
        }

        return response()->json([
            'book' => $book,
            'book_copies' => $book->bookCopies()->get()
        ], 200);
    }
}
