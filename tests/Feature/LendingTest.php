<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BookCopy;
use App\Models\Observation;
use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringStartsWith;
use function Tests\assertLending;

class LendingTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed');
    }

    public function test_post_lending(): void
    {
        $student = Student::first();
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => array_map(fn($o) => $o['id'], $b['observations'])
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
        }
    }

    public function test_post_lending_with_empty_observations(): void
    {
        $student = Student::first();
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => []
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
        }
    }

    public function test_post_lending_without_observations(): void
    {
        $student = Student::first();
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id']
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
        }
    }

    public function test_post_lending_no_student(): void
    {
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => array_map(fn($o) => $o['id'], $b['observations'])
            ],
            $bookCopiesArray
        );

        $lending = [
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->post(route('lendings.store'), $lending);
        $response->assertStatus(422);
        $json = $response->json();
        assertEquals('Error en la validación', $json['message']);
        assertEquals('Se necesita el estudiante al que hacer el préstamo', $json['errors']['student_id'][0]);
    }

    public function test_post_lending_book_copy_no_status(): void
    {
        $student = Student::first();
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'observations_id' => array_map(fn($o) => $o['id'], $b['observations'])
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->post(route('lendings.store'), $lending);
        $response->assertStatus(422);
        $json = $response->json();
        assertEquals('Error en la validación', $json['message']);
        assertEquals('El estado de los libros a prestar es obligatorio', $json['errors']['book_copies.0.status_id'][0]);
        assertEquals('El estado de los libros a prestar es obligatorio', $json['errors']['book_copies.1.status_id'][0]);
        assertEquals('El estado de los libros a prestar es obligatorio', $json['errors']['book_copies.2.status_id'][0]);
    }

    public function test_post_lending_transaction_error(): void
    {
        $student = Student::first();
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::get()->take(3)->toArray();
        $maxObservationId = Observation::max('id');

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => ['id' => $maxObservationId + 1]
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->post(route('lendings.store'), $lending);
        $response->assertStatus(500);
        assertStringStartsWith('Error en la transacción de creación de un préstamo: ', $response->json()['data']['message']);
    }
}
