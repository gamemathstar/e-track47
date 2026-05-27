<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;

/**
 * Phase 2 foundation smoke tests. Deliberately DB-free: they prove the v2 lane is
 * wired, responses are raw, the error contract is correct, and — critically — that
 * none of it bleeds into the web or v1 lanes (guardrails GR3/GR4).
 */
class FoundationTest extends TestCase
{
    public function test_ping_is_reachable_and_returns_raw_json(): void
    {
        $response = $this->getJson('/api/v2/ping');

        $response->assertOk()
            ->assertJson(['status' => 'ok', 'apiVersion' => 'v2'])
            // Raw response: no `{ data: … }` envelope (API_REFERENCE.md §5).
            ->assertJsonMissingPath('data');
    }

    public function test_unknown_v2_route_returns_structured_error_contract(): void
    {
        $response = $this->getJson('/api/v2/does-not-exist');

        $response->assertNotFound()
            ->assertExactJson([
                'code' => 'not_found',
                'message' => 'The requested resource was not found.',
            ]);
    }

    public function test_v2_error_contract_does_not_leak_into_web_routes(): void
    {
        // An unknown WEB route must still yield the framework's default 404,
        // never the v2 JSON error body (proves the handler is route-scoped).
        $response = $this->get('/this-web-route-does-not-exist');

        $response->assertNotFound();
        $this->assertStringNotContainsString('"code":"not_found"', $response->getContent());
    }

    public function test_web_login_screen_still_renders(): void
    {
        // The web session-auth entry point is unaffected by the v2 build.
        $this->get('/login')->assertOk();
    }
}
