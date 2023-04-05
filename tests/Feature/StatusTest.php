<?php
namespace Tests\Feature;

use App\Models\Status;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use Tests\TestCase;

class StatusTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed');
    }

    public function test_get_statuses(): void
    {
        $response = $this->get(route('statuses.index'));
        $response->assertStatus(200);

        dump($response->json());
    }
}
