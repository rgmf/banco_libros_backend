<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Book;
use App\Models\BookCopy;
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
        $books->each(function($book) use ($status) {
            BookCopy::factory()->count(5)->create([
                'book_id' => $book->id,
                'status_id' => $status->random()->id
            ]);
        });
    }
}
