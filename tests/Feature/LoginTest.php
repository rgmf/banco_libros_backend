<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

use function PHPUnit\Framework\assertTrue;

class LoginTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function assertTimestamp(Carbon $expected, Carbon $actual, int $secondsTolerance)
    {
        $diffInSeconds = $expected->diffInSeconds($actual);
        $this->assertTrue($diffInSeconds <= $secondsTolerance, "The timestamps are not equal within the given tolerance.");
    }

    public function test_login(): void
    {
        $mockResponse = [
            'user' => [
                'email' => 'example@example.com'
            ],
            'token' => 'mocked_token'
        ];
        Http::shouldReceive('post')->andReturn(Mockery::mock([
            'status' => 200,
            'json' => $mockResponse
        ]));

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);
        assertTrue(array_key_exists('token', $response->json()));

        $user = User::where('name', 'mock_name')->first();
        $this->assertTimestamp(Carbon::createFromTimeString($user->gdc_token_expiration), Carbon::now()->addHour(), 60);
    }

    public function test_several_logins_same_user(): void
    {
        $mockResponse = [
            'user' => [
                'email' => 'example@example.com'
            ],
            'token' => 'mocked_token'
        ];
        Http::shouldReceive('post')->andReturn(Mockery::mock([
            'status' => 200,
            'json' => $mockResponse
        ]));

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);

        $this->assertEquals(1, User::where('name', 'mock_name')->get()->count());
    }

    public function test_login_without_name(): void
    {
        $response = $this->post(route('login.login'), ['password' => 'password']);
        $response->assertStatus(422);
    }

    public function test_login_without_password(): void
    {
        $response = $this->post(route('login.login'), ['name' => 'name']);
        $response->assertStatus(422);
    }

    public function test_login_without_data(): void
    {
        $response = $this->post(route('login.login'));
        $response->assertStatus(422);
    }

    public function test_valid_logout(): void
    {
        $mockResponse = [
            'user' => [
                'email' => 'example@example.com'
            ],
            'token' => 'mocked_token'
        ];
        Http::shouldReceive('post')->andReturn(Mockery::mock([
            'status' => 200,
            'json' => $mockResponse
        ]));

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);
        $this->assertEquals(1, User::where('name', 'mock_name')->get()->count());

        $response = $this->withHeaders(
            ['Authorization' => 'Bearer mocked_token']
        )->post(route('login.logout', ['name' => 'mock_name']));
        $response->assertStatus(200);
        $this->assertEquals(0, User::where('name', 'mock_name')->get()->count());
    }

    public function test_invalid_logout(): void
    {
        $mockResponse = [
            'user' => [
                'email' => 'example@example.com'
            ],
            'token' => 'mocked_token'
        ];
        Http::shouldReceive('post')->andReturn(Mockery::mock([
            'status' => 200,
            'json' => $mockResponse
        ]));

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);
        $this->assertEquals(1, User::where('name', 'mock_name')->get()->count());

        $response = $this->post(route('login.logout', ['name' => 'noname']));
        $response->assertStatus(401);
        $this->assertEquals(1, User::where('name', 'mock_name')->get()->count());
    }

    public function test_login_and_operation_authorization_ok(): void
    {
        $mockResponse = [
            'user' => [
                'email' => 'example@example.com'
            ],
            'token' => 'mocked_token'
        ];
        Http::shouldReceive('post')->andReturn(Mockery::mock([
            'status' => 200,
            'json' => $mockResponse
        ]));

        $response = $this->post(route('login.login'), ['name' => 'mock_name', 'password' => 'mock_password']);
        $response->assertStatus(200);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mocked_token'
        ])->get(route('books.index'));

        $response->assertStatus(200);
    }

    public function test_authorization_bad_bearer_format(): void
    {
        $user = new User();
        $user->name = 'test';
        $user->email = 'test@test.com';
        $user->password = bcrypt('test');
        $user->gdc_token = 'test';
        $user->gdc_token_expiration = Carbon::now()->addHour();
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => $user->gdc_token_expiration
        ])->get(route('books.index'));

        $response->assertStatus(401);
    }

    public function test_authorization_with_past_token_date_expiration(): void
    {
        $user = new User();
        $user->name = 'test';
        $user->email = 'test@test.com';
        $user->password = bcrypt('test');
        $user->gdc_token = 'test';
        $user->gdc_token_expiration = Carbon::now()->subHour();
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->gdc_token_expiration
        ])->get(route('books.index'));

        $response->assertStatus(401);
    }
}
