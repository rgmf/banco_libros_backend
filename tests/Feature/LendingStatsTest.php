<?php
namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Cohort;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use Tests\TestCase;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Student;
use App\Models\Lending;
use App\Models\Status;
use App\Models\BookCopy;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function Tests\assertByBookCopyLendingHistory;
use function Tests\assertByStudentLendingHistory;

class LendingStatsTest extends TestCase
{
    use DatabaseMigrations;

    const COHORTS = 3;
    const STUDENTS_BY_COHORT = 5;

    private $headers;
    private $students = [];
    private $cohorts = [];
    private $academicYears = [];

    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed');

        for ($i = 1; $i <= self::COHORTS; $i++) {
            $ay = new AcademicYear();
            $ay->name = 'Academic Year ' . $i;
            $ay->save();
            $this->academicYears[] = $ay;

            $c = new Cohort();
            $c->name = 'Cohort ' . $i;
            $c->save();
            $this->cohorts[] = $c;

            for ($j = 0; $j < self::STUDENTS_BY_COHORT; $j++) {
                $s = new Student();
                $s->nia = 'NIA' . $i . $j;
                $s->name = 'Name' . $i . $j;
                $s->lastname1 = 'Lastname' . $i . $j;
                $s->lastname2 = 'Lastname' . $i . $j;
                $s->cohort_id = $c->id;
                $s->save();
                $this->students[] = $s;
            }
        }

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

    public function test_get_list_lending_stats_by_bookcopy_doest_not_exists(): void
    {
        $bookCopy = Bookcopy::first();
        $barcode = $bookCopy->barcode;

        BookCopy::first()->delete();

        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingbookcopy', $barcode));

        $response->assertStatus(404);
    }

    public function test_get_list_lending_stats_by_bookcopy_without_lendings(): void
    {
        $bookCopy = Bookcopy::first();

        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingbookcopy', $bookCopy->barcode));

        $response->assertStatus(200);
        assertByBookCopyLendingHistory($response->json()['data']);
        assertCount(0, $response->json()['data']['lendings']);
    }

    public function test_get_list_lending_stats_by_bookcopy_1_lending(): void
    {
        $bookCopy = Bookcopy::first();
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => $bookCopy->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => null,
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => null,
            'lending_comment' => null
        ]);

        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingbookcopy', $bookCopy->barcode));

        $response->assertStatus(200);
        assertByBookCopyLendingHistory($response->json()['data']);
        assertCount(1, $response->json()['data']['lendings']);
    }

    public function test_get_list_lending_stats_by_bookcopy_several_lendings(): void
    {
        $bookCopy = BookCopy::first();

        $academicYears = AcademicYear::all();
        $academicYears->each(function($academicYear) use ($bookCopy) {
            Lending::create([
                'student_id' => $this->students[0]->id,
                'book_copy_id' => $bookCopy->id,
                'academic_year_id' => $academicYear->id,
                'lending_date' => '2020-09-10 10:10:10',
                'returned_date' => '2021-06-10 10:10:10',
                'lending_status_id' => Status::first()->id,
                'returned_status_id' => Status::first()->id,
                'lending_comment' => 'Así se prestó',
                'returned_comment' => 'Y así se devolvió'
            ]);
        });

        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingbookcopy', $bookCopy->barcode));

        $response->assertStatus(200);
        assertByBookCopyLendingHistory($response->json()['data']);
        assertCount($academicYears->count(), $response->json()['data']['lendings']);
    }

    public function test_get_list_lending_stats_by_student_doest_not_exists(): void
    {
        $student = Student::first();
        $id = $student->id;

        Student::first()->delete();

        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingstudent', $id));

        $response->assertStatus(404);
    }

    public function test_get_list_lending_stats_by_student_without_lendings(): void
    {
        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingstudent', $this->students[0]->id));

        $response->assertStatus(200);
        assertByStudentLendingHistory($response->json()['data']);
        assertCount(0, $response->json()['data']['lendings']);
    }

    public function test_get_list_lending_stats_by_student_1_lending(): void
    {
        $bookCopy = BookCopy::first();
        $studentId = $this->students[0]->id;

        Lending::create([
            'student_id' => $studentId,
            'book_copy_id' => $bookCopy->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => null,
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => null,
            'lending_comment' => null
        ]);

        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingstudent', $studentId));

        $response->assertStatus(200);
        assertByStudentLendingHistory($response->json()['data']);
        assertCount(1, $response->json()['data']['lendings']);
    }

    public function test_get_list_lending_stats_by_student_several_lendings(): void
    {
        $bookCopy = BookCopy::first();
        $studentId = $this->students[0]->id;

        $academicYears = AcademicYear::all();
        $academicYears->each(function($academicYear) use ($bookCopy, $studentId) {
            Lending::create([
                'student_id' => $studentId,
                'book_copy_id' => $bookCopy->id,
                'academic_year_id' => $academicYear->id,
                'lending_date' => '2020-09-10 10:10:10',
                'returned_date' => '2021-06-10 10:10:10',
                'lending_status_id' => Status::first()->id,
                'returned_status_id' => Status::first()->id,
                'lending_comment' => 'Así se prestó',
                'returned_comment' => 'Y así se devolvió'
            ]);
        });

        $response = $this->withHeaders($this->headers)
            ->get(route('lendingsstats.listlendingstudent', $studentId));

        $response->assertStatus(200);
        assertByStudentLendingHistory($response->json()['data']);
        assertCount($academicYears->count(), $response->json()['data']['lendings']);
    }

    /**
     * There are not lendings, so there are not results (stats).
     */
    public function test_get_list_students_return_by_cohort_empty(): void
    {
        $response = $this->withHeaders($this->headers)->get(
            route(
                'lendingsstats.liststudentsreturn',
                [
                    'cohort_id' => $this->cohorts[0]->id,
                    'academic_year_id' => $this->academicYears[0]->id
                ]
            )
        );
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(0, count($data));
    }

    /**
     * There are lendings but there are any lendings returned.
     */
    public function test_get_list_students_return_by_cohort_not_return(): void
    {
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => null,
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => null,
            'lending_comment' => null
        ]);

        $response = $this->withHeaders($this->headers)->get(
            route(
                'lendingsstats.liststudentsreturn',
                [
                    'cohort_id' => $this->students[0]->cohort_id,
                    'academic_year_id' => $this->academicYears[0]->id
                ]
            )
        );
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(0, count($data));
    }

    /**
     * Only one student with lendings and it is returned.
     */
    public function test_get_list_students_return_by_cohort_one_lendings_one_student(): void
    {
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => '2020-10-11 10:10:10',
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => Status::first()->id,
            'lending_comment' => 'With comment'
        ]);

        $response = $this->withHeaders($this->headers)->get(
            route(
                'lendingsstats.liststudentsreturn',
                [
                    'cohort_id' => $this->students[0]->cohort_id,
                    'academic_year_id' => $this->academicYears[0]->id
                ]
            )
        );
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(1, count($data));
    }

    /**
     * Two lendings for the same student in several academic years.
     */
    public function test_get_list_students_return_by_cohort_several_lendings_only_one_returned(): void
    {
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => '2020-10-11 10:10:10',
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => Status::first()->id,
            'lending_comment' => 'With comment'
        ]);
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[1]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => null,
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => null,
            'lending_comment' => null
        ]);

        $response = $this->withHeaders($this->headers)->get(
            route(
                'lendingsstats.liststudentsreturn',
                [
                    'cohort_id' => $this->students[0]->cohort_id,
                    'academic_year_id' => $this->academicYears[0]->id
                ]
            )
        );
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(1, count($data));
    }

    public function test_get_list_students_return_by_cohort_several_lendings_returned_in_academic_year(): void
    {
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => '2020-10-11 10:10:10',
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => Status::first()->id,
            'lending_comment' => 'With comment'
        ]);
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[1]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => null,
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => null,
            'lending_comment' => null
        ]);

        $response = $this->withHeaders($this->headers)->get(
            route(
                'lendingsstats.liststudentsreturn',
                [
                    'cohort_id' => $this->students[0]->cohort_id,
                    'academic_year_id' => $this->academicYears[1]->id
                ]
            )
        );
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(0, count($data));
    }

    public function test_get_list_students_return_by_cohort_several_lendings_returned_several_students_same_cohort(): void
    {
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => '2020-10-11 10:10:10',
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => Status::first()->id,
            'lending_comment' => 'With comment'
        ]);
        Lending::create([
            'student_id' => $this->students[1]->id,
            'book_copy_id' => BookCopy::all()[1]->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => '2020-10-11 10:10:10',
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => Status::first()->id,
            'lending_comment' => null
        ]);

        $response = $this->withHeaders($this->headers)->get(
            route(
                'lendingsstats.liststudentsreturn',
                [
                    'cohort_id' => $this->students[0]->cohort_id,
                    'academic_year_id' => $this->academicYears[0]->id
                ]
            )
        );
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(2, count($data));
    }

    public function test_get_list_students_return_by_cohort_several_lendings_returned_several_students_different_cohort(): void
    {
        Lending::create([
            'student_id' => $this->students[0]->id,
            'book_copy_id' => BookCopy::first()->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => '2020-10-11 10:10:10',
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => Status::first()->id,
            'lending_comment' => 'With comment'
        ]);
        Lending::create([
            'student_id' => $this->students[self::STUDENTS_BY_COHORT]->id,
            'book_copy_id' => BookCopy::all()[1]->id,
            'academic_year_id' => $this->academicYears[0]->id,
            'lending_date' => '2020-10-10 10:10:10',
            'returned_date' => '2020-10-11 10:10:10',
            'lending_status_id' => Status::first()->id,
            'returned_status_id' => Status::first()->id,
            'lending_comment' => null
        ]);

        $response = $this->withHeaders($this->headers)->get(
            route(
                'lendingsstats.liststudentsreturn',
                [
                    'cohort_id' => $this->students[0]->cohort_id,
                    'academic_year_id' => $this->academicYears[0]->id
                ]
            )
        );
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(1, count($data));
    }
}
