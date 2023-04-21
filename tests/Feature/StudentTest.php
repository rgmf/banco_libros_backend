<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

class StudentTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed');
    }

    public function test_get_api_books(): void
    {
        $response = $this->get(route('students.index'));
        $response->assertStatus(200);

        $data = $response->json()['data'];
        assertEquals(5, count($data));

        foreach ($data as $student) {
            assertTrue(array_key_exists('id', $student));
            assertTrue(array_key_exists('name', $student));
            assertTrue(array_key_exists('lastname1', $student));
            assertTrue(array_key_exists('lastname2', $student));
            assertTrue(array_key_exists('cohort_id', $student));
        }
    }

    public function test_post_api_students_bulk(): void
    {
        $beginStudents = Student::get();

        $students = [
            'students' => [
                ['nia' => '11111111', 'name' => 'Name', 'lastname1' => 'Lastname 1'],
                ['nia' => '22222222', 'name' => 'Name', 'lastname1' => 'Lastname 1']
            ]
        ];
        $response = $this->post(route('students.storebulk'), $students);

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
                ['nia' => '22222222', 'name' => 'Name', 'lastname1' => 'Lastname 1']
            ]
        ];
        $response = $this->post(route('students.storebulk'), $students);

        $response->assertStatus(409);
        assertEquals('Hay estudiantes que ya existen', $response->json()['data']['message']);
        assertEquals(count($beginStudents), count(Student::get()));
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
        $response = $this->post(route('students.storebulk'), $students);

        $response->assertStatus(422);
        assertEquals('Error en la validación', $response->json()['message']);
        assertEquals('El NIA es obligatorio', $response->json()['errors']['students.0.nia'][0]);
        assertEquals(count($beginStudents), count(Student::get()));
    }
}
