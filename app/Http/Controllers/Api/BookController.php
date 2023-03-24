<?php
namespace App\Http\Controllers\Api;

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
        $book = Book::create([
            'isbn' => $request->input('isbn'),
            'title' => $request->input('title'),
            'author' => $request->input('author'),
            'publisher' => $request->input('publisher'),
            'volumes' => $request->input('volumes')
        ]);
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
                'error' => 'El libro que solicitas no existe'
            ], 404);
        }

        return response()->json([
            'book' => $book
        ], 200);
    }

    public function update(BookUpdateRequest $request, Book $book)
    {
        $book->fill($request->only($request->keys()));
        $book->save();
        return response()->json([
            'message' => 'Libro actualizado correctamente',
            'book' => $book
        ], 201);
    }

    public function destroy(int $id)
    {
        try {
            $book = Book::findOrFail($id);
            $book->delete();
            return response()->json(['message' => 'El libro ha sido eliminado correctamente'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'El libro que intenta eliminar no existe'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Se produjo un error al eliminar el libro'], 500);
        }
    }
}
