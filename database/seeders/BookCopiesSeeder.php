<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Observation;
use App\Models\Status;

class BookCopiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->create1ESOBookCopies();

        /*$books = Book::get();
        $status = Status::get();
        $observations = Observation::get();
        $books->each(function($book) use ($status, $observations) {
            $bookCopies = BookCopy::factory()->count(5)->create([
                'book_id' => $book->id,
                'status_id' => $status->random()->id
            ]);

            $bookCopies->each(function($bookCopy) use ($observations) {
                $bookCopy->observations()->attach($observations->random(rand(1, 3)));
            });
        });*/
    }

    private function create1ESOBookCopies()
    {
        $books = $books = Book::orderBy('id')
            ->with('grade')
            ->whereHas('grade', function ($query) {
                $query->where('name', '1º ESO');
            })
            ->get();
        $observations = Observation::get();

        // New book copies for all books
        $books->each(function($book) {
            BookCopy::factory()->count(5)->create([
                'book_id' => $book->id,
                'status_id' => 1
            ]);
        });

        // Good book copies for all books
        $books->each(function($book) use ($observations) {
            $bookCopies = BookCopy::factory()->count(5)->create([
                'book_id' => $book->id,
                'status_id' => 2
            ]);

            $bookCopies->each(function($bookCopy) use ($observations) {
                $bookCopy->observations()->attach($observations->random(rand(1, 3)));
            });
        });

        // Acceptable book copies for all books
        $books->each(function($book) use ($observations) {
            $bookCopies = BookCopy::factory()->count(5)->create([
                'book_id' => $book->id,
                'status_id' => 3
            ]);

            $bookCopies->each(function($bookCopy) use ($observations) {
                $bookCopy->observations()->attach($observations->random(rand(1, 3)));
            });
        });

        // Non-usable book bopies for all books
        $books->each(function($book) use ($observations) {
            $bookCopies = BookCopy::factory()->count(5)->create([
                'book_id' => $book->id,
                'status_id' => 4
            ]);

            $bookCopies->each(function($bookCopy) use ($observations) {
                $bookCopy->observations()->attach($observations->random(rand(1, 3)));
            });
        });
    }
}
