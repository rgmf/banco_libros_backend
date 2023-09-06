<?php
namespace App\Http\Controllers\Api;

use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\InfoResource;
use App\Models\Book;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with('grade')->get();
        return new BookCollection($books);
    }

    public function store(BookRequest $request)
    {
        try {
            $book = Book::create([
                'isbn' => $request->input('isbn'),
                'title' => $request->input('title'),
                'author' => $request->input('author'),
                'publisher' => $request->input('publisher'),
                'volumes' => $request->input('volumes'),
                'grade_id' => $request->input('grade_id')
            ]);
            $book->load('grade');
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return new ErrorResource(409, 'El libro ya existe', $exception);
            } else {
                return new ErrorResource(500, 'Error al insertar el libro', $exception);
            }
        }

        return new BookResource($book, 201);
    }

    public function show(int $id)
    {
        $book = Book::with('grade')->find($id);
        if (!$book) {
            return new ErrorResource(404, 'El libro que solicitas no existe');
        }
        return new BookResource($book);
    }

    public function update(BookUpdateRequest $request, Book $book)
    {
        try {
            $book->fill($request->only($request->keys()));
            $book->save();
            $book->load('grade');
            return new BookResource($book, 201);
        } catch (QueryException $exception) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                return new ErrorResource(409, 'Ya existe un libro con esos datos', $exception);
            } else {
                return new ErrorResource(500, 'Error al actualizar el libro', $exception);
            }
        }
    }

    public function destroy(int $id)
    {
        try {
            $book = Book::findOrFail($id);
            $book->delete();
            return new InfoResource(200, 'El libro ha sido eliminado correctamente');
        } catch (ModelNotFoundException $e) {
            return new ErrorResource(404, 'El libro que intentas eliminar no existe', $e);
        } catch (\Throwable $e) {
            return new ErrorResource(500, 'Se produjo un error al eliminar el libro', $e);
        }
    }

    public function getBookCopies(int $id)
    {
        $book = Book::with('grade')->with('bookCopies')->find($id);
        if (!$book) {
            return new ErrorResource(404, 'El libro del que buscas copias no existe');
        }
        return new BookResource($book);
    }
}
