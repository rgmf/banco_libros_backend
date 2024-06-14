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
use function PHPUnit\Framework\assertEquals;

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
