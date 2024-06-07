<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Cohort;
use App\Models\Grade;
use App\Models\Lending;
use App\Models\Observation;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

use function PHPSTORM_META\type;
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

    public function test_create_a_lending_with_observations_and_comments_check_lending_comment(): void
    {
        $o1 = new Observation;
        $o1->title = 'Observation 1';
        $o1->save();

        $o2 = new Observation;
        $o2->title = 'Observation 2';
        $o2->save();

        $o3 = new Observation;
        $o3->title = 'Observation 3';
        $o3->save();

        $bookCopies = BookCopy::doesntHave('lendings')->with('observations')->get()->take(3);
        $bookCopiesArray = [];
        foreach ($bookCopies as $bookCopy) {
            $bookCopy->observations()->detach();
            $bookCopy->observations()->attach([$o1->id, $o2->id, $o3->id]);
            $bookCopy->save();
            $bookCopiesArray[] = BookCopy::with('observations')->find($bookCopy->id);
        }

        $studentWithNoLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);
        $academicYear = AcademicYear::first();

        $bookCopiesArrayFiltered = array_map(
            fn($b) => [
                'id' => $b->id,
                'status_id' => $b->status_id,
                'observations_id' => array_map(fn($o) => $o['id'], $b->observations()->get()->toArray()),
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
            $editedLending = Lending::find($lending['id']);
            assertNotNull($editedLending->lending_date);
            assertEquals("Observation 1\nObservation 2\nObservation 3\nNew comment", $editedLending->lending_comment);
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

    public function test_return_lending_with_only_needed_data_returned_status_id(): void
    {
        $lending = Lending::first();
        $bookCopy = $lending->bookCopy;
        $statusId = $bookCopy->status_id < 3 ? $bookCopy->status_id + 1 : 1;
        $data = [
            'returned_status_id' => $statusId
        ];

        assertNull($lending->returned_status_id);
        assertNull($lending->returned_date);

        $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->returned_status_id);
        assertNotNull($editedLending->returned_date);
        assertEquals('', $editedLending->returned_comment);

        $bookCopy = BookCopy::findOrFail($lending->bookCopy->id);
        assertEquals($statusId, $bookCopy->status_id);
        assertTrue(empty($bookCopy->observations->toArray()));
        assertEquals('', $bookCopy->comment);
    }

    public function test_return_lending_with_observations_id_empty(): void
    {
        $lending = Lending::first();
        $bookCopy = $lending->bookCopy;
        $statusId = $bookCopy->status_id < 3 ? $bookCopy->status_id + 1 : 1;
        $data = [
            'returned_status_id' => $statusId,
            'observations_id' => [],
            'comment' => 'Comment number 1'
        ];

        assertNull($lending->returned_status_id);
        assertNull($lending->returned_date);

        $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->returned_status_id);
        assertNotNull($editedLending->returned_date);
        assertEquals('Comment number 1', $editedLending->returned_comment);

        $bookCopy = BookCopy::findOrFail($lending->bookCopy->id);
        assertEquals($statusId, $bookCopy->status_id);
        assertTrue(empty($bookCopy->observations->toArray()));
        assertEquals('Comment number 1', $bookCopy->comment);
    }

    public function test_return_lending_with_comment_empty_and_without_observations_id(): void
    {
        $lending = Lending::first();
        $bookCopy = $lending->bookCopy;
        $statusId = $bookCopy->status_id < 3 ? $bookCopy->status_id + 1 : 1;
        $data = [
            'returned_status_id' => $statusId,
            'comment' => ''
        ];

        assertNull($lending->returned_status_id);
        assertNull($lending->returned_date);

        $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->returned_status_id);
        assertNotNull($editedLending->returned_date);
        assertEquals('', $editedLending->returned_comment);

        $bookCopy = BookCopy::findOrFail($lending->bookCopy->id);
        assertEquals($statusId, $bookCopy->status_id);
        assertTrue(empty($bookCopy->observations->toArray()));
        assertEquals('', $bookCopy->comment);
    }

    public function test_return_lending_with_status_id_and_observations(): void
    {
        $o1 = new Observation;
        $o1->title = 'Observation 1';
        $o1->save();

        $o2 = new Observation;
        $o2->title = 'Observation 2';
        $o2->save();

        $o3 = new Observation;
        $o3->title = 'Observation 3';
        $o3->save();

        $allObservationsId = [$o1->id, $o2->id, $o3->id];
        $lending = Lending::first();
        $bookCopy = $lending->bookCopy;
        $statusId = $bookCopy->status_id < 3 ? $bookCopy->status_id + 1 : 1;
        $data = [
            'returned_status_id' => $statusId,
            'observations_id' => $allObservationsId
        ];

        assertNull($lending->returned_status_id);
        assertNull($lending->returned_date);

        $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->returned_status_id);
        assertNotNull($editedLending->returned_date);
        assertEquals("Observation 1\nObservation 2\nObservation 3", $editedLending->returned_comment);

        $bookCopy = BookCopy::findOrFail($lending->bookCopy->id);
        assertEquals($statusId, $bookCopy->status_id);
        $bookCopyObservationsId =$bookCopy->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($allObservationsId as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
        assertEquals('', $bookCopy->comment);
    }

    public function test_return_lending_with_all(): void
    {
        $o1 = new Observation;
        $o1->title = 'Observation 1';
        $o1->save();

        $o2 = new Observation;
        $o2->title = 'Observation 2';
        $o2->save();

        $o3 = new Observation;
        $o3->title = 'Observation 3';
        $o3->save();

        $allObservationsId = [$o1->id, $o2->id, $o3->id];
        $lending = Lending::first();
        $bookCopy = $lending->bookCopy;
        $statusId = $bookCopy->status_id < 3 ? $bookCopy->status_id + 1 : 1;
        $data = [
            'returned_status_id' => $statusId,
            'observations_id' => $allObservationsId,
            'comment' => 'Random comment'
        ];

        assertNull($lending->returned_status_id);
        assertNull($lending->returned_date);

        $response = $this->withHeaders($this->headers)->put(route('lendings.update', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->returned_status_id);
        assertNotNull($editedLending->returned_date);
        assertEquals("Observation 1\nObservation 2\nObservation 3\nRandom comment", $editedLending->returned_comment);

        $bookCopy = BookCopy::findOrFail($lending->bookCopy->id);
        assertEquals($statusId, $bookCopy->status_id);
        $bookCopyObservationsId =$bookCopy->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($allObservationsId as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
        assertEquals('Random comment', $bookCopy->comment);
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

    public function test_try_return_lending_without_returned_status_id_but_with_observations_and_comment(): void
    {
        $allObservationsId = array_map(fn($o) => $o['id'], Observation::all()->toArray());
        $lending = Lending::first();
        $data = [
            'observations_id' => $allObservationsId,
            'comment' => 'A comment'
        ];

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

    public function test_edit_lending_with_all(): void
    {
        $o1 = new Observation;
        $o1->title = 'Observation 1';
        $o1->save();

        $o2 = new Observation;
        $o2->title = 'Observation 2';
        $o2->save();

        $o3 = new Observation;
        $o3->title = 'Observation 3';
        $o3->save();

        $allObservationsId = [$o1->id, $o2->id, $o3->id];
        $lending = Lending::first();
        $bookCopy = $lending->bookCopy;
        $statusId = $bookCopy->status_id < 3 ? $bookCopy->status_id + 1 : 1;
        $data = [
            'status_id' => $statusId,
            'observations_id' => $allObservationsId,
            'comment' => 'Random comment'
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals("Observation 1\nObservation 2\nObservation 3\nRandom comment", $editedLending->lending_comment);

        $bookCopy = BookCopy::find($bookCopy->id);
        assertEquals($statusId, $bookCopy->status_id);
        assertEquals("Random comment", $bookCopy->comment);
        $bookCopyObservationsId = $bookCopy->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($allObservationsId as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
    }

    public function test_edit_lending_with_only_status_id(): void
    {
        $lending = Lending::first();
        $lendingComment = $lending->lending_comment;
        $bookCopy = $lending->bookCopy;
        $statusId = $bookCopy->status_id < 3 ? $bookCopy->status_id + 1 : 1;
        $data = [
            'status_id' => $statusId
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals($lendingComment, $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertEquals($bookCopy->comment, $bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($bookCopy->observations()->getQuery()->pluck('observation_id')->toArray() as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
    }

    public function test_edit_lending_with_only_observations(): void
    {
        $o1 = new Observation;
        $o1->title = 'Observation 1';
        $o1->save();

        $o2 = new Observation;
        $o2->title = 'Observation 2';
        $o2->save();

        $o3 = new Observation;
        $o3->title = 'Observation 3';
        $o3->save();

        $allObservationsId = [$o1->id, $o2->id, $o3->id];
        $lending = Lending::first();
        $statusId = $lending->lending_status_id;
        $data = [
            'observations_id' => $allObservationsId
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals("Observation 1\nObservation 2\nObservation 3", $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($lending->bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertEquals($lending->bookCopy->comment, $bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($allObservationsId as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
    }

    public function test_edit_lending_with_only_comment(): void
    {
        $lending = Lending::first();
        $statusId = $lending->lending_status_id;
        $data = [
            'comment' => 'Nuevo comentario'
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals("Nuevo comentario", $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($lending->bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertEquals("Nuevo comentario", $bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($lending->bookCopy->observations()->getQuery()->pluck('observation_id')->toArray() as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
    }

    public function test_edit_lending_empty(): void
    {
        $lending = Lending::first();
        $statusId = $lending->lending_status_id;
        $lendingComment = $lending->lending_comment;
        $data = [];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals($lendingComment, $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($lending->bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertEquals($lending->bookCopy->comment, $bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($lending->bookCopy->observations()->getQuery()->pluck('observation_id')->toArray() as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
    }

    public function test_edit_lending_observations_empty(): void
    {
        $lending = Lending::first();
        $lending->lending_comment = 'Comentario';
        $lending->save();
        
        $statusId = $lending->lending_status_id;
        $lendingComment = $lending->lending_comment;
        $data = [
            'observations_id' => []
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals('', $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($lending->bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertEquals($lending->bookCopy->comment, $bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        assertTrue(empty($bookCopyObservationsId));
    }

    public function test_edit_lending_comment_empty(): void
    {
        $lending = Lending::first();
        $lending->lending_comment = 'Comentario';
        $lending->save();

        $statusId = $lending->lending_status_id;
        $lendingComment = $lending->lending_comment;
        $data = [
            'comment' => ''
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals('', $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($lending->bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertNull($bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($lending->bookCopy->observations()->getQuery()->pluck('observation_id')->toArray() as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
    }

    public function test_edit_lending_observations_empty_with_comment(): void
    {
        $lending = Lending::first();
        $lending->lending_comment = 'Comentario';
        $lending->save();

        $statusId = $lending->lending_status_id;
        $lendingComment = $lending->lending_comment;
        $data = [
            'observations_id' => [],
            'comment' => 'Nuevo comentario'
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals("Nuevo comentario", $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($lending->bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertEquals('Nuevo comentario', $bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        assertTrue(empty($bookCopyObservationsId));
    }

    public function test_edit_lending_comment_empty_with_observations(): void
    {
        $o1 = new Observation;
        $o1->title = 'Observation 1';
        $o1->save();

        $o2 = new Observation;
        $o2->title = 'Observation 2';
        $o2->save();

        $o3 = new Observation;
        $o3->title = 'Observation 3';
        $o3->save();

        $allObservationsId = [$o1->id, $o2->id, $o3->id];

        $lending = Lending::first();
        $lending->lending_comment = 'Comentario';
        $lending->save();

        $statusId = $lending->lending_status_id;
        $lendingComment = $lending->lending_comment;
        $data = [
            'observations_id' => $allObservationsId,
            'comment' => ''
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.edit', $lending->id), $data);

        $response->assertStatus(201);

        $editedLending = Lending::find($lending->id);
        assertEquals($statusId, $editedLending->lending_status_id);
        assertEquals("Observation 1\nObservation 2\nObservation 3", $editedLending->lending_comment);

        $bookCopyUpdated = BookCopy::find($lending->bookCopy->id);
        assertEquals($statusId, $bookCopyUpdated->status_id);
        assertNull($bookCopyUpdated->comment);
        $bookCopyObservationsId = $bookCopyUpdated->observations()->getQuery()->pluck('observation_id')->toArray();
        foreach ($allObservationsId as $observationId) {
            assertTrue(in_array($observationId, $bookCopyObservationsId));
        }
    }

    public function test_lending_grades_messaging_ok(): void
    {
        $firstGrade = Grade::first();
        $gradeArray = [];

        $books = Book::where('grade_id', $firstGrade->id)->get();
        $students = [];
        foreach ($books as $book) {
            $bookCopies = $book->bookCopies;
            foreach ($bookCopies as $bc) {
                foreach ($bc->lendings()->whereNull('returned_date')->get() as $lending) {
                    if (count(array_filter($students, fn($s) => $s->id == $lending->student->id)) == 0) {
                        $students[] = $lending->student;
                    }
                }
            }
        }

        $data = [
            'grades' => ['id' => $firstGrade->id]
        ];

        $response = $this->withHeaders($this->headers)->post(route('lendings.messaging'), $data);
        $response->assertStatus(200);
        assertEquals(strval(count($students)), $response->json()['data']['messages']);
    }

    public function test_lending_grades_messaging_without_grades(): void
    {
        $data = [
            'grades' => []
        ];
        $response = $this->withHeaders($this->headers)->post(route('lendings.messaging'), $data);
        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('Tienes que indicar, al menos, un curso al que enviar el e-mail', $response->json()['errors']['grades'][0]);
    }

    public function test_lending_grades_messaging_with_grades_not_integers(): void
    {
        $data = [
            'grades' => ['grade1', 'grade2']
        ];
        $response = $this->withHeaders($this->headers)->post(route('lendings.messaging'), $data);
        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('No se han indicado los cursos correctamente (deberían ser los identificadores del curso)', $response->json()['errors']['grades.0'][0]);
        assertEquals('No se han indicado los cursos correctamente (deberían ser los identificadores del curso)', $response->json()['errors']['grades.1'][0]);
    }
}
