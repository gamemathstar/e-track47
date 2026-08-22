<?php

namespace Tests\Feature\Api\V2;

use App\Models\ApiRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Proves the curated migration baseline builds a correct, DB-backed test schema
 * on the isolated `mysql_test` connection (trackerx_test) — the foundation for
 * Phase 3 feature tests. RefreshDatabase runs migrate:fresh on that connection
 * only; the live `trackerx` DB is never touched.
 */
class DatabaseBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_pdcu_tables_exist(): void
    {
        foreach ([
            'users', 'password_reset_tokens', 'user_roles', 'facilitator_sectors',
            'sectors', 'frameworks', 'commitments', 'deliverables', 'kpis',
            'kpi_targets', 'performance_trackings', 'files', 'data_entry_accesses',
            'galleries', 'gallery_comments', 'notifications', 'api_refresh_tokens',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_users_table_has_v2_and_real_columns(): void
    {
        foreach (['full_name', 'phone_number', 'image_url', 'fcm_token', 'must_change_password', 'avatar_key'] as $col) {
            $this->assertTrue(Schema::hasColumn('users', $col), "users missing column: {$col}");
        }
    }

    public function test_performance_tracking_enum_has_full_workflow_states(): void
    {
        $type = DB::select('SHOW COLUMNS FROM performance_trackings LIKE "confirmation_status"')[0]->Type;

        foreach (['Pending Sector Head Approval', 'Pending Facilitator', 'Pending Coordinator', 'Confirmed', 'Rejected'] as $state) {
            $this->assertStringContainsString($state, $type, "enum missing state: {$state}");
        }
    }

    public function test_refresh_token_round_trips(): void
    {
        $raw = 'test-refresh-token-value';
        $token = ApiRefreshToken::create([
            'user_id' => 1,
            'token_hash' => ApiRefreshToken::hashToken($raw),
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertTrue($token->isActive());

        $found = ApiRefreshToken::where('token_hash', ApiRefreshToken::hashToken($raw))->first();
        $this->assertNotNull($found);

        $found->revoke();
        $this->assertFalse($found->fresh()->isActive());
    }
}
