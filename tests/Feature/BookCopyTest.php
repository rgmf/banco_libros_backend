<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use App\Models\Book;
use App\Models\Status;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;
use function Tests\assertBook;
use function Tests\assertBookCopy;

class BookCopyTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed');
    }

    public function test_get_book_copies(): void
    {
        $book = Book::first();

        $response = $this->get(route('books.copies', $book->id));
        $response->assertStatus(200);

        assertTrue(array_key_exists('book', $response->json()));
        assertTrue(array_key_exists('book_copies', $response->json()));

        assertBook($response->json()['book']);

        assertTrue(count($response->json()['book_copies']) > 0);
        foreach ($response->json()['book_copies'] as $bookCopy) {
            assertBookCopy($bookCopy);
        }

        assertEquals(
            $response->json()['book']['id'],
            $response->json()['book_copies'][0]['book_id']
        );
    }

    public function test_post_generate_book_copies(): void
    {
        $book = Book::first();
        $status = Status::first();
        $count = 5;

        $response = $this->post(route('bookcopies.store', [$book->id, $count, $status->id]));
        $response->assertStatus(200);

        $bookCopies = $response->json()['book_copies'];
        $barcodes = array_filter(array_map(fn($i) => $i['barcode'], $bookCopies), fn($i) => strlen($i) == 13);
        assertEquals('Copias del libro creadas correctamente', $response->json()['message']);
        assertTrue(count($bookCopies) == $count);
        assertTrue(count($bookCopies) == count(array_unique($barcodes)));
    }

    public function test_post_generate_book_copies_book_not_exists(): void
    {
        Book::whereNotNull('id')->delete();
        $bookId = 1;
        $statusId = 1;
        $count = 5;

        $response = $this->post(route('bookcopies.store', [$bookId, $count, $statusId]));
        $response->assertStatus(404);

        assertEquals('El libro del que quieres crear copias no existe', $response->json()['message']);
    }

    public function test_post_generate_book_copies_status_not_exists(): void
    {
        $bookId = Book::first()->id;
        $statusId = 20;
        $count = 5;

        $response = $this->post(route('bookcopies.store', [$bookId, $count, $statusId]));
        $response->assertStatus(404);

        assertEquals('El estado indicado para los libros no existe', $response->json()['message']);
    }

}
