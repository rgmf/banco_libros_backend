<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BookCopy;
use App\Models\Cohort;
use App\Models\Lending;
use App\Models\Observation;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertStringStartsWith;
use function PHPUnit\Framework\assertTrue;
use function Tests\assertLending;

class LendingTest extends TestCase
{
    use DatabaseMigrations;

    private $headers;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed');

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

    public function test_create_a_lending_with_several_book_copies(): void
    {
        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::doesntHave('lendings')->with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => array_map(fn($o) => $o['id'], $b['observations'])
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
        }
    }

    public function test_create_a_lending_with_book_copies_comments(): void
    {
        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::doesntHave('lendings')->with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => array_map(fn($o) => $o['id'], $b['observations']),
                'comment' => 'New comment'
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
        }

        foreach ($bookCopiesArrayFiltered as $bookCopyArray) {
            $b = BookCopy::find($bookCopyArray['id']);
            assertEquals($b->comment, 'New comment');
        }
    }

    public function test_append_several_book_copies_to_a_current_student_lending(): void
    {
        $student = Student::whereHas('lendings')->first();
        $numberOfLendedBooks = $student->lendings()->count();
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::doesntHave('lendings')->with('observations')->get()->take(3)->toArray();

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

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        # Return the three lendings added
        assertEquals(3, count($data));
        # Into the returned data it can find the lendings
        assertEquals($numberOfLendedBooks + 3, count($data[0]['student']['lendings']));
        # Into the database the student has all expected lendings
        assertEquals($numberOfLendedBooks + 3, Student::find($student->id)->lendings()->count());
    }

    public function test_try_to_lend_several_book_copies_already_lended_to_an_student_without_lending(): void
    {
        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::whereHas('lendings')->with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => array_map(fn($o) => $o['id'], $b['observations'])
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(409);

        $data = $response->json()['data'];
        assertEquals('El libro ya está prestado', $data['message']);
    }

    public function test_try_to_lend_several_book_copies_already_lended_to_an_student_with_lendings(): void
    {
        $student = Student::whereHas('lendings')->first();
        $academicYear = AcademicYear::first();
        # Get book copies lended to other students
        $ids = array_map(fn($l) => $l['id'], $student->lendings()->get()->toArray());
        $bookCopiesArray = BookCopy::whereHas('lendings', function ($query) use ($ids) {
            $query->whereNotIn('id', $ids);
        })->with('observations')->get()->take(1)->toArray();

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

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(409);

        $data = $response->json()['data'];
        assertEquals('El libro ya está prestado', $data['message']);
    }

    public function test_try_to_lend_several_book_copies_already_lended_other_academic_year(): void
    {
        $newAcademicYear = new AcademicYear();
        $newAcademicYear->name = 'NewYear';
        $newAcademicYear->save();
        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $bookCopiesArray = BookCopy::whereHas('lendings')->with('observations')->get()->take(4)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => array_map(fn($o) => $o['id'], $b['observations'])
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $newAcademicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(409);
    }

    public function test_lend_a_book_lended_and_returned_last_year(): void
    {
        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $academicYear = AcademicYear::first();
        $newAcademicYear = new AcademicYear();
        $newAcademicYear->name = 'New Year';
        $newAcademicYear->save();
        $bookCopiesArray = BookCopy::doesntHave('lendings')->with('observations')->get()->take(1)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => []
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        foreach ($response['data'] as $lending) {
            $return = [
                'returned_status_id' => $lending['book_copy']['status_id']
            ];
            $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending['id']), $return);
            $response->assertStatus(201);
        }

        $newYearLending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $newAcademicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $newYearLending);
        $response->assertStatus(200);
    }

    public function test_lend_book_copies_to_student_with_last_year_lending_returned(): void
    {
        $student = Student::whereHas('lendings')->first();
        $newAcademicYear = new AcademicYear();
        $newAcademicYear->name = 'New Year';
        $newAcademicYear->save();
        $bookCopy = BookCopy::doesntHave('lendings')->first();

        $student->lendings()->get()->each(function($lending) {
            $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending['id']), ['returned_status_id' => 2]);
            $response->assertStatus(201);
        });

        $lending = [
            'student_id' => $student->id,
            'academic_year_id' => $newAcademicYear->id,
            'book_copies' => [['id' => $bookCopy->id, 'status_id' => $bookCopy->status_id]]
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(200);
    }

    public function test_try_lending_book_copy_this_year_to_student_with_last_year_lending(): void
    {
        $student = Student::whereHas('lendings')->first();
        $newAcademicYear = new AcademicYear();
        $newAcademicYear->name = 'New Year';
        $newAcademicYear->save();
        $bookCopy = BookCopy::doesntHave('lendings')->first();

        $lending = [
            'student_id' => $student->id,
            'academic_year_id' => $newAcademicYear->id,
            'book_copies' => [['id' => $bookCopy->id, 'status_id' => $bookCopy->status_id]]
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(500);
    }

    public function test_lend_several_book_copies_with_empty_observations(): void
    {
        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::doesntHave('lendings')->with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id'],
                'observations_id' => []
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
        }
    }

    public function test_lend_several_book_copies_with_without_observations(): void
    {
        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::doesntHave('lendings')->with('observations')->get()->take(3)->toArray();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b['id'],
                'status_id' => $b['status_id']
            ],
            $bookCopiesArray
        );

        $lending = [
            'student_id' => $studentWithNoLendings->id,
            'academic_year_id' => $academicYear->id,
            'book_copies' => $bookCopiesArrayFiltered
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
        }
    }

    public function test_try_a_lending_without_student(): void
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

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(422);
        $json = $response->json();
        assertEquals('Error en la validación', $json['message']);
        assertEquals('Se necesita el estudiante al que hacer el préstamo', $json['errors']['student_id'][0]);
    }

    public function test_try_to_lend_several_book_copies_without_status(): void
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

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(422);
        $json = $response->json();
        assertEquals('Error en la validación', $json['message']);
        assertEquals('El estado de los libros a prestar es obligatorio', $json['errors']['book_copies.0.status_id'][0]);
        assertEquals('El estado de los libros a prestar es obligatorio', $json['errors']['book_copies.1.status_id'][0]);
        assertEquals('El estado de los libros a prestar es obligatorio', $json['errors']['book_copies.2.status_id'][0]);
    }

    public function test_try_to_lend_several_books_with_non_existing_observations(): void
    {
        $student = Student::first();
        $academicYear = AcademicYear::first();
        $bookCopiesArray = BookCopy::doesntHave('lendings')->get()->take(3)->toArray();
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

        $response = $this->withHeaders($this->headers)->post(route('lendings.store'), $lending);
        $response->assertStatus(500);
        assertStringStartsWith('Error al prestar el libro', $response->json()['data']['message']);
    }

    public function test_return_lending(): void
    {
        $lending = Lending::first();
        $data = [
            'returned_status_id' => 1
        ];

        assertNull($lending->returned_status_id);
        assertNull($lending->returned_date);

        $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals(1, $editedLending->returned_status_id);
        assertNotNull($editedLending->returned_date);
    }

    public function test_try_return_lending_without_returned_status_id(): void
    {
        $lending = Lending::first();
        $data = [];

        assertNull($lending->returned_status_id);
        assertNull($lending->returned_date);

        $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending->id), $data);

        $response->assertStatus(422);

        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals(
            'Se necesita el estado en que se devuelve la copia del libro',
            $response->json()['errors']['returned_status_id'][0]
        );
    }
}
