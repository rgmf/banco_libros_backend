<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use function Tests\assertStudent;

use function PHPUnit\Framework\assertEquals;

class StudentTest extends TestCase
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

    public function test_get_api_students(): void
    {
        $response = $this->withHeaders($this->headers)->get(route('students.index'));
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(5, count($data));

        foreach ($data as $student) {
            assertStudent($student);
            /*assertTrue(array_key_exists('id', $student));
            assertTrue(array_key_exists('name', $student));
            assertTrue(array_key_exists('lastname1', $student));
            assertTrue(array_key_exists('lastname2', $student));
            assertTrue(array_key_exists('cohort_id', $student));
            assertTrue(array_key_exists('cohort', $student));
            assertTrue(array_key_exists('id', $student['cohort']));
            assertTrue(array_key_exists('name', $student['cohort']));*/
        }
    }

    public function test_get_api_student(): void
    {
        $student = Student::get()->first();
        $response = $this->withHeaders($this->headers)->get(route('students.show', $student->id));
        $response->assertStatus(200);

        $arrayObj = $response->json()['data'];

        assertStudent($arrayObj);
    }

    public function test_get_api_student_not_exists(): void
    {
        $students = Student::get();
        $ids = [];
        $students->each(function($student) use (&$ids) {
            $ids[] = $student->id;
        });

        sort($ids);
        $idNotExists = $ids[array_key_last($ids)] + 1;
        $response = $this->withHeaders($this->headers)->get(route('students.show', $idNotExists));
        $response->assertStatus(404);

        assertEquals('El/la estudiante que solicitas no existe', $response->json()['data']['message']);
    }


    public function test_post_api_students_bulk(): void
    {
        $beginStudents = Student::get();

        $students = [
            'students' => [
                ['nia' => '10101010', 'name' => 'Name', 'lastname1' => 'Lastname 1'],
                ['nia' => '20202020', 'name' => 'Name', 'lastname1' => 'Lastname 1']
            ]
        ];
        $response = $this->withHeaders($this->headers)->post(route('students.storebulk'), $students);

        $response->assertStatus(200);
        assertEquals(2, count($response->json()['data']));
        assertEquals(count($beginStudents) + 2, count(Student::get()));
    }

    public function test_post_api_students_bulk_already_exists(): void
    {
        $beginStudents = Student::get();

        $students = [
            'students' => [
                ['nia' => $beginStudents[0]['nia'], 'name' => 'Name', 'lastname1' => 'Lastname 1'],
                ['nia' => '20202020', 'name' => 'Name', 'lastname1' => 'Lastname 1']
            ]
        ];
        $response = $this->withHeaders($this->headers)->post(route('students.storebulk'), $students);

        $response->assertStatus(200);
        assertEquals(1, count($response->json()['data']));
        assertEquals(count($beginStudents) + 1, count(Student::get()));
    }

    public function test_post_api_students_bulk_required_error(): void
    {
        $beginStudents = Student::get();

        $students = [
            'students' => [
                ['name' => 'Name', 'lastname1' => 'Lastname 1'],
                ['nia' => '22222222', 'name' => 'Name', 'lastname1' => 'Lastname 1']
            ]
        ];
        $response = $this->withHeaders($this->headers)->post(route('students.storebulk'), $students);

        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('El NIA es obligatorio', $response->json()['errors']['students.0.nia'][0]);
        assertEquals(count($beginStudents), count(Student::get()));
    }
}
