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
            'title' => 'Castellano 1º ESO',
            'author' => 'Caste Llano',
            'publisher' => 'Casteditorial'
        ]);
        Book::create([
            'id' => 2,
            'isbn' => '2222222222222',
            'title' => 'Matemáticas 1º ESO',
            'author' => 'Mate Máticas',
            'publisher' => 'Mateditorial'
        ]);
        Book::create([
            'id' => 3,
            'isbn' => '3333333333333',
            'title' => 'Historia 1º ESO',
            'author' => 'Histo Ría',
            'publisher' => 'Histoeditorial'
        ]);
        Book::create([
            'id' => 4,
            'isbn' => '4444444444444',
            'title' => 'Informática 1º ESO',
            'author' => 'Infor Mática',
            'publisher' => 'Infoeditorial'
        ]);
        //Book::factory()->count(5)->create();
    }
}
