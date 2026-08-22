<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;

/**
 * Deploy / CI readiness probe (`GET /api/v2/_health`). Confirms the endpoint is
 * unauthenticated and reports a known shape with sensible values when the
 * runtime is healthy (keys present, DB reachable).
 */
class HealthTest extends TestCase
{
    public function test_health_endpoint_is_public_and_reports_status(): void
    {
        // No bearer token; the probe must answer anyway.
        $response = $this->getJson('/api/v2/_health');

        $response->assertJsonStructure(['api', 'passport', 'db']);
        $this->assertContains($response->json('passport'), ['ok', 'missing_keys', 'missing_client']);
        $this->assertContains($response->json('db'), ['ok', 'fail']);
        $this->assertSame('v2.0.0', $response->json('api'));

        // Status code follows the probe: 200 healthy, 503 unhealthy.
        $this->assertContains($response->status(), [200, 503]);
    }

    public function test_ensure_passport_keys_command_is_idempotent(): void
    {
        // Running twice in a row must succeed both times (the command exits 0
        // whether the keys were already present or just generated).
        $this->artisan('pdcu:ensure-passport-keys')->assertExitCode(0);
        $this->artisan('pdcu:ensure-passport-keys')->assertExitCode(0);
    }
}
