<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function Tests\assertBook;

use App\Models\Book;
use App\Models\Grade;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class BookTest extends TestCase
{
    use DatabaseMigrations;

    private $headers;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => 'BooksSeeder']);

        $token = "testtoken";

        $user = new User();
        $user->name = "test";
        $user->email = "test@test.com";
        $user->password = "test";
        $user->gdc_token = $token;
        $user->gdc_token_expiration = Carbon::tomorrow();
        $user->save();

        $this->headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ];
    }

    public function test_get_api_books(): void
    {
        $response = $this->withHeaders($this->headers)->get(route('books.index'));
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(4, count($data));

        foreach ($data as $book) {
            assertBook($book);
        }
    }

    public function test_get_api_book(): void
    {
        $book = Book::get()->first();
        $response = $this->withHeaders($this->headers)->get(route('books.show', $book->id));
        $response->assertStatus(200);

        $arrayObj = $response->json()['data'];

        assertBook($arrayObj);
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
        $response = $this->withHeaders($this->headers)->get(route('books.show', $idNotExists));
        $response->assertStatus(404);

        assertEquals('El libro que solicitas no existe', $response->json()['data']['message']);
    }

    public function test_delete_api_book(): void
    {
        $books = Book::get();

        $response = $this->withHeaders($this->headers)->delete(route('books.destroy', $books->first()));
        $response->assertStatus(200);

        $response = $this->withHeaders($this->headers)->delete(route('books.destroy', $books->last()->id));
        $response->assertStatus(200);
        assertEquals('El libro ha sido eliminado correctamente', $response->json()['data']['message']);

        assertCount($books->count() - 2, Book::get());
    }

    public function test_delete_api_book_not_exists(): void
    {
        $book = Book::first();

        $this->withHeaders($this->headers)->delete(route('books.destroy', $book->id));
        $response = $this->withHeaders($this->headers)->delete(route('books.destroy', $book->id));
        $response->assertStatus(404);
        assertEquals('El libro que intentas eliminar no existe', $response->json()['data']['message']);
    }

    public function test_post_api_book(): void
    {
        $countStart = Book::get()->count();
        $data = [
            'isbn' => '1112223334445',
            'title' => 'Book title',
            'author' => 'Book author',
            'publisher' => 'Book publisher',
            'volumes' => 1,
            'grade_id' => Grade::first()->id
        ];

        $response = $this->withHeaders($this->headers)->post(route('books.store'), $data);
        $response->assertStatus(201);
        $response->assertJsonIsObject();

        $arrayObj = $response->json()['data'];

        assertBook($arrayObj);

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
            'volumes' => 1,
            'grade_id' => Grade::first()->id
        ];

        $this->withHeaders($this->headers)->post(route('books.store'), $data);
        $response = $this->withHeaders($this->headers)->post(route('books.store'), $data);
        $response->assertStatus(409);
        assertEquals('El libro ya existe', $response->json()['data']['message']);
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
        $response = $this->withHeaders($this->headers)->post(route('books.store'), $data);

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

        $response = $this->withHeaders($this->headers)->put(route('books.update', $book->id), $data);

        $response->assertStatus(201);
        assertEquals('Book testing title', Book::find($book->id)->title);

        $arrayObj = $response->json()['data'];

        assertBook($arrayObj);
    }

    public function test_put_api_book_already_exists_error(): void
    {
        $data = [
            'isbn' => '1112223334445',
            'title' => 'Book title',
            'author' => 'Book author',
            'publisher' => 'Book publisher',
            'volumes' => 1,
            'grade_id' => Grade::first()->id
        ];
        $this->withHeaders($this->headers)->post(route('books.store'), $data);

        $book = Book::where('isbn', '<>', $data['isbn'])->first();
        $data = [
            'isbn' => '1112223334445',
            'title' => 'Book testing title'
        ];

        $response = $this->withHeaders($this->headers)->put(route('books.update', $book->id), $data);
        $response->assertStatus(409);
        assertEquals('Ya existe un libro con esos datos', $response->json()['data']['message']);
    }

    public function test_put_api_book_try_null_data(): void
    {
        $book = Book::first();
        $data = [
            'title' => null
        ];

        $response = $this->withHeaders($this->headers)->put(route('books.update', $book->id), $data);

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

        $response = $this->withHeaders($this->headers)->put(route('books.update', $book->id), $data);

        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('El número de vólumenes de un libro es obligatorio y, al menos, 1', $response->json()['errors']['volumes'][0]);
        assertNotNull(Book::find($book->id)->title);
    }
}
