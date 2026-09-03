<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_root_route_redirects_to_setup_when_no_users_exist(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/setup');
    }
}
