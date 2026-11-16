<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_health_mengembalikan_200_dan_status_database(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'database' => 'connected',
            ]);
    }

    public function test_halaman_utama_bisa_dibuka(): void
    {
        $this->get('/')->assertOk()->assertSee('KosanHub');
    }
}
