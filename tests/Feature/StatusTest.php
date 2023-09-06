<?php
namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use Tests\TestCase;

class StatusTest extends TestCase
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

    public function test_get_statuses(): void
    {
        $response = $this->withHeaders($this->headers)->get(route('statuses.index'));
        $response->assertStatus(200);
    }
}
