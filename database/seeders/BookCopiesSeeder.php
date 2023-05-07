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
        $books = Book::get();
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
        });
    }
}
