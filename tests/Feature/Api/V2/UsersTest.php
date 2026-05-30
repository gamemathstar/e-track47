<?php

namespace Tests\Feature\Api\V2;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.9 users & security — admin directory + me flows + security log.
 */
class UsersTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_list_users_filters_by_role_and_returns_required_shape(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $this->makeSectorHead($sector, ['full_name' => 'Amina Egbe', 'email' => 'amina@pdcu.gov.ng']);
        $this->makeDataAdmin($sector, ['full_name' => 'Tunde A.', 'email' => 'tunde@pdcu.gov.ng']);

        $admin = $this->makeUser(['target_entity' => 'System'], 'System Admin');
        Passport::actingAs($admin, [], 'api');

        $this->getJson('/api/v2/users')
            ->assertOk()
            ->assertJsonStructure([['id', 'name', 'email', 'role', 'sector', 'roleLabel', 'sectorLabel', 'initials', 'accent']]);

        // Role filter (sector_head only).
        $this->getJson('/api/v2/users?role=sector_head')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.role', 'sector_head');
    }

    public function test_list_users_orders_by_role_rank_governor_first(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);

        // Seed one user per active role, deliberately created in non-rank order.
        $this->makeDataAdmin($sector,        ['full_name' => 'Tunde Data',       'email' => 'tunde@pdcu.gov.ng']);
        $this->makeSectorHead($sector,       ['full_name' => 'Amina Head',       'email' => 'amina@pdcu.gov.ng']);
        $this->makeUser(['target_entity' => 'State'],                  'Governor',          );
        $this->makeUser(['target_entity' => 'System'],                 'System Admin',      );
        $this->makeUser(['target_entity' => 'Deliverable'],            'Coordinator',       );
        $this->makeUser(['target_entity' => 'Deliverable'],            'Deputy Coordinator',);
        $this->makeFacilitator($sector);

        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $rolesInOrder = collect($this->getJson('/api/v2/users')->assertOk()->json())
            ->pluck('role')
            ->all();

        // Governor first, then coordinator/deputy/sector_head/facilitator/data_admin/system_admin.
        // (Two system_admins were created — the actor and the seeded one — both land at the end.)
        $this->assertSame('governor',          $rolesInOrder[0]);
        $this->assertSame('coordinator',       $rolesInOrder[1]);
        $this->assertSame('deputy_coordinator',$rolesInOrder[2]);
        $this->assertSame('sector_head',       $rolesInOrder[3]);
        $this->assertSame('facilitator',       $rolesInOrder[4]);
        $this->assertSame('data_admin',        $rolesInOrder[5]);
        $this->assertSame('system_admin',      $rolesInOrder[6]);
        $this->assertSame('system_admin',      $rolesInOrder[7]);
    }

    public function test_user_detail(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $target = $this->makeSectorHead($sector, [
            'full_name' => 'Amina Egbe',
            'email' => 'amina.egbe@jigawastate.gov.ng',
            'phone_number' => '+2348012345678',
        ]);

        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $this->getJson("/api/v2/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('id', (string) $target->id)
            ->assertJsonPath('name', 'Amina Egbe')
            ->assertJsonPath('email', 'amina.egbe@jigawastate.gov.ng')
            ->assertJsonPath('phone', '+2348012345678')
            ->assertJsonPath('roleLabel', 'Sector Head')
            ->assertJsonPath('sectorLabel', 'Health')
            ->assertJsonStructure(['initials', 'accent', 'role', 'fullLegalName', 'staffId', 'joinDate', 'bio', 'twoFactorStatus', 'isVerified']);
    }

    public function test_create_user_with_photo(): void
    {
        Storage::fake('public');
        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $response = $this->post('/api/v2/users', [
            'fullName' => 'New User',
            'email' => 'new@pdcu.gov.ng',
            'phone' => '+2348012345678',
            'role' => 'facilitator',
            'photo' => UploadedFile::fake()->image('avatar.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(202);
        $user = User::where('email', 'new@pdcu.gov.ng')->first();
        $this->assertNotNull($user);
        $this->assertSame(1, (int) $user->must_change_password);
        $this->assertNotSame('', $user->image_url);
    }

    public function test_create_user_duplicate_email_conflicts(): void
    {
        $this->makeUser(['email' => 'dup@pdcu.gov.ng']);
        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $this->postJson('/api/v2/users', ['fullName' => 'X', 'email' => 'dup@pdcu.gov.ng', 'phone' => '+234', 'role' => 'facilitator'])
            ->assertStatus(409)->assertJsonPath('code', 'conflict');
    }

    public function test_create_user_validation_errors(): void
    {
        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $this->postJson('/api/v2/users', [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['fullName', 'email', 'phone', 'role']]);
    }

    public function test_change_my_password(): void
    {
        $user = $this->makeUser(['password' => 'oldsecret1']);
        Passport::actingAs($user, [], 'api');

        $this->postJson('/api/v2/users/me/password', ['currentPassword' => 'oldsecret1', 'newPassword' => 'brandnew123'])
            ->assertNoContent();

        $this->assertTrue(Hash::check('brandnew123', $user->fresh()->password));
    }

    public function test_change_my_password_wrong_current(): void
    {
        $user = $this->makeUser(['password' => 'oldsecret1']);
        Passport::actingAs($user, [], 'api');

        $this->postJson('/api/v2/users/me/password', ['currentPassword' => 'wrong', 'newPassword' => 'brandnew123'])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['currentPassword']]);
    }

    public function test_update_my_photo(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        Passport::actingAs($user, [], 'api');

        $this->post('/api/v2/users/me/photo', ['photo' => UploadedFile::fake()->image('me.png')], ['Accept' => 'application/json'])
            ->assertNoContent();

        $this->assertNotSame('', $user->fresh()->image_url);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api'); // no role
        $this->getJson('/api/v2/users')->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_security_log_filter_logins(): void
    {
        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $this->getJson('/api/v2/users/security-log?filter=logins')->assertOk();
        $this->getJson('/api/v2/users/security-log?filter=changes')->assertOk()->assertExactJson([]);
    }

    public function test_users_endpoints_require_auth(): void
    {
        $this->getJson('/api/v2/users')->assertStatus(401);
    }
}
