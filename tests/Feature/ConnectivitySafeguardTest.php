<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectivitySafeguardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test active connection heartbeat returns online status.
     */
    public function test_active_connection_heartbeat_returns_online_status(): void
    {
        $response = $this->get(route('api.ping'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'online',
        ]);
    }

    /**
     * Test connectivity disruption stops operations safely with 503 status.
     */
    public function test_connectivity_loss_stops_business_operations_safely(): void
    {
        // Simulate client reporting lost connectivity via heartbeat
        $response = $this->withHeaders([
            'X-Simulate-Offline' => 'true',
        ])->get(route('api.ping'));

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'offline',
        ]);
    }
}
