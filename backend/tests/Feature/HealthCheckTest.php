<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test health endpoint returns OK.
     */
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
            ])
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    /**
     * Test health endpoint has valid timestamp.
     */
    public function test_health_endpoint_has_valid_timestamp(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertNotEmpty($data['timestamp']);
        
        // Verify it's a valid ISO 8601 timestamp
        $timestamp = \DateTime::createFromFormat(\DateTime::ATOM, $data['timestamp']);
        $this->assertInstanceOf(\DateTime::class, $timestamp);
    }
}
