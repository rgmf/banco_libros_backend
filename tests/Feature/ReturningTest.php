<?php

namespace Tests\Feature;

use Carbon\Carbon;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use Tests\TestCase;
use App\Models\Lending;
use App\Models\Student;
use App\Models\Cohort;
use App\Models\User;
use App\Models\BookCopy;
use App\Models\Observation;

use function PHPUnit\Framework\assertNotNull;
use function Tests\assertLending;
/*
class ReturningTest extends TestCase
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

    private function assertTimestamp(Carbon $expected, Carbon $actual, int $secondsTolerance)
    {
        $diffInSeconds = $expected->diffInSeconds($actual);
        $this->assertTrue($diffInSeconds <= $secondsTolerance, "The timestamps are not equal within the given tolerance.");
    }

    public function test_lending_return_all_books_with_same_status_and_observations_that_lending(): void
    {
        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
                 $returning['book_copies'][] = [
                'id' => $l->book_copy_id,
                'status_id' => $l->bookCopy->status_id,
                'observations_id' => $l->bookCopy->observations != null ? array_map(fn($o) => $o['id'], $l->bookCopy->observations->toArray()) : []
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
            $this->assertTimestamp(Carbon::createFromTimeString($lending['returned_date']), Carbon::now(), 30);
            $this->assertEquals($lending['lending_status_id'], $lending['returned_status_id']);
        }

        // There are not lendings for this student after the return.
        $response = $this->withHeaders($this->headers)->get(route('lendings.indexbystudent', $studentId));
        $response->assertStatus(404);
        $this->assertEquals($response->json()['data']['message'], 'No existe préstamo para el estudiante dado');
    }

    public function test_lending_return_all_books_with_a_new_comment_and_same_observations_that_lending(): void
    {
        $newCommentForAllBc = 'New comment ' . Carbon::now();

        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
                 $returning['book_copies'][] = [
                'id' => $l->book_copy_id,
                'status_id' => $l->bookCopy->status_id,
                'comment' => $newCommentForAllBc,
                'observations_id' => $l->bookCopy->observations != null ? array_map(fn($o) => $o['id'], $l->bookCopy->observations->toArray()) : []
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
            $this->assertTimestamp(Carbon::createFromTimeString($lending['returned_date']), Carbon::now(), 30);
            $this->assertEquals($lending['lending_status_id'], $lending['returned_status_id']);
        }

        foreach ($returning['book_copies'] as $bc) {
            $bookCopy = BookCopy::find($bc['id']);
            $this->assertNotNull($bookCopy);
            $this->assertEquals($bookCopy->comment, $newCommentForAllBc);
            $this->assertTrue($bookCopy->observations->count() > 0);
        }

        // There are not lendings for this student after the return.
        $response = $this->withHeaders($this->headers)->get(route('lendings.indexbystudent', $studentId));
        $response->assertStatus(404);
        $this->assertEquals($response->json()['data']['message'], 'No existe préstamo para el estudiante dado');
    }

    public function test_lending_return_all_books_with_new_comment_and_empty_observations(): void
    {
        $newCommentForAllBc = 'New comment ' . Carbon::now();

        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
                 $returning['book_copies'][] = [
                'id' => $l->book_copy_id,
                'status_id' => $l->bookCopy->status_id,
                'comment' => $newCommentForAllBc,
                'observations_id' => []
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
            $this->assertTimestamp(Carbon::createFromTimeString($lending['returned_date']), Carbon::now(), 30);
            $this->assertEquals($lending['lending_status_id'], $lending['returned_status_id']);
        }

        foreach ($returning['book_copies'] as $bc) {
            $bookCopy = BookCopy::find($bc['id']);
            $this->assertNotNull($bookCopy);
            $this->assertEquals($bookCopy->comment, $newCommentForAllBc);
            $this->assertTrue($bookCopy->observations->count() == 0);
        }

        // There are not lendings for this student after the return.
        $response = $this->withHeaders($this->headers)->get(route('lendings.indexbystudent', $studentId));
        $response->assertStatus(404);
        $this->assertEquals($response->json()['data']['message'], 'No existe préstamo para el estudiante dado');
    }

    public function test_lending_return_all_books_with_all_but_1_observations(): void
    {
        $newCommentForAllBc = 'New comment ' . Carbon::now();
        $allObservationsButTheFirstOne = Observation::all()->toArray();
        array_shift($allObservationsButTheFirstOne);

        $this->assertTrue(count($allObservationsButTheFirstOne) > 0);

        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
                 $returning['book_copies'][] = [
                'id' => $l->book_copy_id,
                'status_id' => $l->bookCopy->status_id,
                'comment' => $newCommentForAllBc,
                'observations_id' => array_map(fn($i) => $i['id'], $allObservationsButTheFirstOne)
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
            $this->assertTimestamp(Carbon::createFromTimeString($lending['returned_date']), Carbon::now(), 30);
            $this->assertEquals($lending['lending_status_id'], $lending['returned_status_id']);
        }

        foreach ($returning['book_copies'] as $bc) {
            $bookCopy = BookCopy::find($bc['id']);
            $this->assertNotNull($bookCopy);
            $this->assertEquals($bookCopy->comment, $newCommentForAllBc);
            $this->assertTrue($bookCopy->observations->count() == count($allObservationsButTheFirstOne));
        }

        // There are not lendings for this student after the return.
        $response = $this->withHeaders($this->headers)->get(route('lendings.indexbystudent', $studentId));
        $response->assertStatus(404);
        $this->assertEquals($response->json()['data']['message'], 'No existe préstamo para el estudiante dado');
    }

    public function test_lending_return_some_books(): void
    {
        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        $i = 1;
        foreach ($allStudentLendings as $l) {
            if ($i % 2 == 0) {
                $returning['book_copies'][] = [
                    'id' => $l->book_copy_id,
                    'status_id' => $l->bookCopy->status_id,
                    'observations_id' => []
                ];
            }
            $i++;
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(200);

        $data = $response->json()['data'];
        foreach ($data as $lending) {
            assertLending($lending);
            $this->assertTimestamp(Carbon::createFromTimeString($lending['returned_date']), Carbon::now(), 30);
            $this->assertEquals($lending['lending_status_id'], $lending['returned_status_id']);
        }

        foreach ($returning['book_copies'] as $bc) {
            $bookCopy = BookCopy::find($bc['id']);
            $this->assertNotNull($bookCopy);
        }

        // There are lendings for this student after the return.
        $response = $this->withHeaders($this->headers)->get(route('lendings.indexbystudent', $studentId));
        $response->assertStatus(200);
        $data = $response->json()['data'];
        $this->assertTrue(count($data) > 0);
        $this->assertTrue($allStudentLendings->count() > count($data));
    }

    public function test_lending_return_try_without_student_id(): void
    {
        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
            $returning['book_copies'][] = [
                'id' => $l->book_copy_id,
                'status_id' => $l->bookCopy->status_id,
                'observations_id' => []
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(422);
        $this->assertEquals($response->json()['errors']['student_id'][0], 'Se necesita el estudiante que tiene prestado estos libros');
    }

    public function test_lending_return_try_without_book_copies(): void
    {
        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id
        ];

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(422);
        $this->assertEquals($response->json()['errors']['book_copies'][0], 'Se necesita, al menos, un libro que devolver');
    }

    public function test_lending_return_try_without_book_copies_id(): void
    {
        $allObservationsButTheFirstOne = Observation::all()->toArray();
        array_shift($allObservationsButTheFirstOne);

        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
                 $returning['book_copies'][] = [
                'status_id' => $l->bookCopy->status_id,
                'observations_id' => array_map(fn($i) => $i['id'], $allObservationsButTheFirstOne)
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(422);
        $this->assertEquals($response->json()['errors']['book_copies.0.id'][0], 'El identificador de los libros a devolver es obligatorio');
    }

    public function test_lending_return_try_without_book_copies_status_id(): void
    {
        $allObservationsButTheFirstOne = Observation::all()->toArray();
        array_shift($allObservationsButTheFirstOne);

        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
            $returning['book_copies'][] = [
                'id' => $l->book_copy_id,
                'observations_id' => array_map(fn($i) => $i['id'], $allObservationsButTheFirstOne)
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(422);
        $this->assertEquals($response->json()['errors']['book_copies.0.status_id'][0], 'El estado de los libros a devolver es obligatorio');
    }

    public function test_lending_return_try_observations_id_is_not_an_array(): void
    {
        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
            $returning['book_copies'][] = [
                'id' => $l->book_copy_id,
                'status_id' => $l->bookCopy->status_id,
                'observations_id' => 1
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(422);
        $this->assertEquals($response->json()['errors']['book_copies.0.observations_id'][0], 'Se necesita un array con los identificadores de las observaciones');
    }

    public function test_lending_return_try_a_book_copy_not_lend(): void
    {
        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;
        $bookCopyNotLend = BookCopy::doesntHave('lendings')->first();

        $returning = [
            'student_id' => $lending->student_id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
            $returning['book_copies'][] = [
                'id' => $bookCopyNotLend->id,
                'status_id' => $l->bookCopy->status_id
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(500);
        $this->assertTrue(strlen($response->json()['data']['message']) > 0);
    }

    public function test_lending_return_try_a_student_without_lendings(): void
    {
        $studentWithoutLendings = Student::create([
            'nia' => '21212121',
            'name' => 'No',
            'lastname1' => 'Lending',
            'lastname2' => 'Student',
            'cohort_id' => Cohort::first()->id
        ]);

        $lending = Lending::with('bookCopy.observations')->first();
        $allStudentLendings = Lending::where('student_id', $lending->student_id)->get();
        $studentId = $lending->student_id;
        $bookCopyNotLend = BookCopy::doesntHave('lendings')->first();

        $returning = [
            'student_id' => $studentWithoutLendings->id,
            'book_copies' => []
        ];
        foreach ($allStudentLendings as $l) {
            $returning['book_copies'][] = [
                'id' => $l->bookCopy->id,
                'status_id' => $l->bookCopy->status_id
            ];
        }

        $response = $this->withHeaders($this->headers)->post(route('lending.return'), $returning);
        $response->assertStatus(500);
        $this->assertTrue(strlen($response->json()['data']['message']) > 0);
    }
}
*/