<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Observation;
use App\Models\Status;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEquals;
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

    public function test_get_bookcopy_by_barcode(): void
    {
        $bookCopy = BookCopy::get()->first();

        $response = $this->get(route('bookcopies.showbybarcode', $bookCopy->barcode));

        $bookCopy = $response->json()['data'];

        assertBookCopy($bookCopy);
        assertTrue(array_key_exists('status', $bookCopy));
        assertTrue(array_key_exists('observations', $bookCopy));
    }

    public function test_get_book_copies(): void
    {
        $book = Book::first();

        $response = $this->get(route('books.copies', $book->id));
        $response->assertStatus(200);

        assertTrue(array_key_exists('data', $response->json()));
        assertTrue(array_key_exists('book_copies', $response->json()['data']));

        assertBook($response->json()['data']);

        assertTrue(count($response->json()['data']['book_copies']) > 0);
        foreach ($response->json()['data']['book_copies'] as $bookCopy) {
            assertBookCopy($bookCopy);
        }

        assertEquals(
            $response->json()['data']['id'],
            $response->json()['data']['book_copies'][0]['book_id']
        );
    }

    public function test_post_generate_book_copies(): void
    {
        $book = Book::first();
        $status = Status::first();
        $count = 5;

        $response = $this->post(route('bookcopies.store', [$book->id, $count, $status->id]));
        $response->assertStatus(200);

        $bookCopies = $response->json()['data'];
        foreach ($bookCopies as $bookCopy) {
            assertBookCopy($bookCopy);
        }

        $barcodes = array_filter(array_map(fn($i) => $i['barcode'], $bookCopies), fn($i) => strlen($i) == 13);
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

        assertEquals('El libro del que quieres crear copias no existe', $response->json()['data']['message']);
    }

    public function test_post_generate_book_copies_status_not_exists(): void
    {
        $bookId = Book::first()->id;
        $statusId = 20;
        $count = 5;

        $response = $this->post(route('bookcopies.store', [$bookId, $count, $statusId]));
        $response->assertStatus(404);

        assertEquals('El estado indicado para los libros no existe', $response->json()['data']['message']);
    }

    public function test_put_update_status(): void
    {
        $bookCopy = BookCopy::first();
        $status = Status::orderBy('id', 'asc')->first();
        $newId = $bookCopy->status_id + 1 > $status->id ? $bookCopy->status_id - 1 : $bookCopy->status_id + 1;
        $todayDate = now();
        $data = [
            'status_id' => $newId,
            'comment' => "A new comment $todayDate"
        ];

        $response = $this->put(route('bookcopies.update', $bookCopy->id), $data);

        $response->assertStatus(201);

        $result = $response->json()['data'];
        assertEquals($newId, $result['status_id']);
        assertNotEquals($bookCopy->status->id, $result['status_id']);
        assertEquals("A new comment $todayDate", $result['comment']);
    }

    public function test_put_update_observations(): void
    {
        $bookCopy = BookCopy::first();
        $observations = Observation::get();
        $ids = array_map(fn($o) => $o['id'], $observations->toArray());
        $data = [
            'observations' => $ids
        ];

        $response = $this->put(route('bookcopies.update', $bookCopy->id), $data);

        $response->assertStatus(201);

        $result = $response->json()['data'];
        assertEquals($observations->count(), count($result['observations']));
    }

    public function test_put_update_with_empty_data(): void
    {
        $bookCopy = BookCopy::first();
        $data = [];

        $response = $this->put(route('bookcopies.update', $bookCopy->id), $data);

        $response->assertStatus(201);

        $result = $response->json()['data'];
        assertEquals($bookCopy->id, $result['id']);
        assertEquals($bookCopy->barcode, $result['barcode']);
        assertEquals($bookCopy->comment, $result['comment']);
        assertEquals($bookCopy->book->id, $result['book_id']);
        assertEquals($bookCopy->status->id, $result['status_id']);
    }
}
