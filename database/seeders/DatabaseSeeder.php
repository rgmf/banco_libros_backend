<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BooksSeeder::class);
        $this->call(BookCopiesSeeder::class);
        $this->call(CohortsSeeder::class);
        $this->call(StudentsSeeder::class);
        $this->call(AcademicYearsSeeder::class);
        $this->call(LendingsSeeder::class);
    }
}
