<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

use App\Models\Book;
use Tests\TestCase;

class BookTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => 'BooksSeeder']);
    }

    public function test_get_api_books(): void
    {
        $response = $this->get(route('books.index'));
        $response->assertStatus(200);
        $response->assertJsonCount(5);

        foreach ($response->json() as $arrayObj) {
            assertTrue(array_key_exists('id', $arrayObj));
            assertTrue(array_key_exists('isbn', $arrayObj));
            assertTrue(array_key_exists('title', $arrayObj));
            assertTrue(array_key_exists('author', $arrayObj));
            assertTrue(array_key_exists('publisher', $arrayObj));
            assertTrue(array_key_exists('volumes', $arrayObj));
            assertTrue(array_key_exists('created_at', $arrayObj));
            assertTrue(array_key_exists('updated_at', $arrayObj));
        }
    }

    public function test_get_api_book(): void
    {
        $book = Book::get()->first();
        $response = $this->get(route('books.show', $book->id));
        $response->assertStatus(200);
        $response->assertJsonIsObject();

        $arrayObj = $response->json()['book'];

        assertTrue(array_key_exists('id', $arrayObj));
        assertTrue(array_key_exists('isbn', $arrayObj));
        assertTrue(array_key_exists('title', $arrayObj));
        assertTrue(array_key_exists('author', $arrayObj));
        assertTrue(array_key_exists('publisher', $arrayObj));
        assertTrue(array_key_exists('volumes', $arrayObj));
        assertTrue(array_key_exists('created_at', $arrayObj));
        assertTrue(array_key_exists('updated_at', $arrayObj));
    }

    public function test_get_api_book_not_exists(): void
    {
        $books = Book::get();
        $ids = [];
        $books->each(function($book) use (&$ids) {
            $ids[] = $book->id;
        });

        sort($ids);
        $idNotExists = $ids[array_key_last($ids)] + 1;
        $response = $this->get(route('books.show', $idNotExists));
        $response->assertStatus(404);

        assertEquals('El libro que solicitas no existe', $response->json()['error']);
    }

    public function test_delete_api_book(): void
    {
        $books = Book::get();

        $response = $this->delete(route('books.destroy', $books->first()));
        $response->assertStatus(200);

        $response = $this->delete(route('books.destroy', $books->last()->id));
        $response->assertStatus(200);
        assertEquals('El libro ha sido eliminado correctamente', $response->json()['message']);

        assertCount($books->count() - 2, Book::get());
    }

    public function test_delete_api_book_not_exists(): void
    {
        $book = Book::first();

        $this->delete(route('books.destroy', $book->id));
        $response = $this->delete(route('books.destroy', $book->id));
        $response->assertStatus(404);
        assertEquals('El libro que intenta eliminar no existe', $response->json()['error']);
    }

    public function test_post_api_book(): void
    {
        $countStart = Book::get()->count();
        $data = [
            'isbn' => '1112223334445',
            'title' => 'Book title',
            'author' => 'Book author',
            'publisher' => 'Book publisher',
            'volumes' => 1
        ];

        $response = $this->post(route('books.store'), $data);
        $response->assertStatus(201);
        $response->assertJsonIsObject();

        assertEquals('Libro insertado correctamente', $response->json()['message']);
        $arrayObj = $response->json()['book'];

        assertTrue(array_key_exists('id', $arrayObj));
        assertTrue(array_key_exists('isbn', $arrayObj));
        assertTrue(array_key_exists('title', $arrayObj));
        assertTrue(array_key_exists('author', $arrayObj));
        assertTrue(array_key_exists('publisher', $arrayObj));
        assertTrue(array_key_exists('volumes', $arrayObj));
        assertTrue(array_key_exists('created_at', $arrayObj));
        assertTrue(array_key_exists('updated_at', $arrayObj));

        $countEnd = Book::get()->count();
        assertEquals($countStart + 1, $countEnd);
    }

    public function test_post_api_book_already_exists_error(): void
    {
        $data = [
            'isbn' => '1112223334445',
            'title' => 'Book title',
            'author' => 'Book author',
            'publisher' => 'Book publisher',
            'volumes' => 1
        ];

        $this->post(route('books.store'), $data);
        $response = $this->post(route('books.store'), $data);
        $response->assertStatus(409);
        assertEquals('El libro ya existe', $response->json()['message']);
    }

    public function test_post_api_book_error_required_data(): void
    {
        $countStart = Book::get()->count();
        $data = [
            'title' => 'Book title',
            'author' => 'Book author',
            'publisher' => 'Book publisher',
            'volumes' => 1
        ];
        $response = $this->post(route('books.store'), $data);

        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('El ISBN es obligatorio y tiene que ser de un máximo de 13 caracteres', $response->json()['errors']['isbn'][0]);

        $countEnd = Book::get()->count();
        assertEquals($countStart, $countEnd);
    }

    public function test_put_api_book(): void
    {
        $book = Book::first();
        $data = [
            'title' => 'Book testing title'
        ];

        $response = $this->put(route('books.update', $book->id), $data);

        $response->assertStatus(201);
        assertEquals('Book testing title', Book::find($book->id)->title);

        assertEquals('Libro actualizado correctamente', $response->json()['message']);
        $arrayObj = $response->json()['book'];

        assertTrue(array_key_exists('id', $arrayObj));
        assertTrue(array_key_exists('isbn', $arrayObj));
        assertTrue(array_key_exists('title', $arrayObj));
        assertTrue(array_key_exists('author', $arrayObj));
        assertTrue(array_key_exists('publisher', $arrayObj));
        assertTrue(array_key_exists('volumes', $arrayObj));
        assertTrue(array_key_exists('created_at', $arrayObj));
        assertTrue(array_key_exists('updated_at', $arrayObj));
    }

    public function test_put_api_book_already_exists_error(): void
    {
        $data = [
            'isbn' => '1112223334445',
            'title' => 'Book title',
            'author' => 'Book author',
            'publisher' => 'Book publisher',
            'volumes' => 1
        ];
        $this->post(route('books.store'), $data);

        $book = Book::where('isbn', '<>', $data['isbn'])->first();
        $data = [
            'isbn' => '1112223334445',
            'title' => 'Book testing title'
        ];

        $response = $this->put(route('books.update', $book->id), $data);
        $response->assertStatus(409);
        assertEquals('Ya existe un libro con esos datos', $response->json()['message']);
    }

    public function test_put_api_book_try_null_data(): void
    {
        $book = Book::first();
        $data = [
            'title' => null
        ];

        $response = $this->put(route('books.update', $book->id), $data);

        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('El título es obligatorio', $response->json()['errors']['title'][0]);
        assertNotNull(Book::find($book->id)->title);
    }

    public function test_put_api_book_try_invalid_data(): void
    {
        $book = Book::first();
        $data = [
            'volumes' => 0
        ];

        $response = $this->put(route('books.update', $book->id), $data);

        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('El número de vólumenes de un libro es obligatorio y, al menos, 1', $response->json()['errors']['volumes'][0]);
        assertNotNull(Book::find($book->id)->title);
    }
}
