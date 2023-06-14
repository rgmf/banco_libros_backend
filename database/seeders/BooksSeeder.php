<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BooksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Book::create([
            'id' => 1,
            'isbn' => '1111111111111',
            'title' => 'Castellano',
            'author' => 'Caste Llano',
            'publisher' => 'Casteditorial',
            'grade_id' => 1
        ]);
        Book::create([
            'id' => 2,
            'isbn' => '2222222222222',
            'title' => 'Matemáticas',
            'author' => 'Mate Máticas',
            'publisher' => 'Mateditorial',
            'grade_id' => 1
        ]);
        Book::create([
            'id' => 3,
            'isbn' => '3333333333333',
            'title' => 'Historia',
            'author' => 'Histo Ría',
            'publisher' => 'Histoeditorial',
            'grade_id' => 1
        ]);
        Book::create([
            'id' => 4,
            'isbn' => '4444444444444',
            'title' => 'Informática',
            'author' => 'Infor Mática',
            'publisher' => 'Infoeditorial',
            'grade_id' => 1
        ]);
        //Book::factory()->count(5)->create();
    }
}
