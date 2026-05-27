<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

/**
 * Test helpers for the v2 auth feature: seed a Passport personal-access client
 * (so real token issuance works against the isolated test DB) and create PDCU
 * users with the real `users` column shape (full_name/phone_number/role/…),
 * setting attributes directly since those columns are intentionally not in the
 * model's $fillable.
 */
trait InteractsWithPdcuAuth
{
    protected function seedPersonalAccessClient(): void
    {
        app(ClientRepository::class)->createPersonalAccessClient(
            null,
            'PDCU Mobile Test',
            'http://localhost'
        );
    }

    protected function makeUser(array $attrs = [], ?string $role = null): User
    {
        $user = new User();
        $user->full_name = $attrs['full_name'] ?? 'Test User';
        $user->email = $attrs['email'] ?? 'user_'.Str::lower(Str::random(8)).'@pdcu.gov.ng';
        $user->phone_number = $attrs['phone_number'] ?? '+2348000000000';
        $user->password = $attrs['password'] ?? 'secret123';
        $user->image_url = $attrs['image_url'] ?? '';
        $user->role = 0;

        if (array_key_exists('must_change_password', $attrs)) {
            $user->must_change_password = $attrs['must_change_password'];
        }

        $user->save();

        if ($role !== null) {
            UserRole::create([
                'user_id' => $user->id,
                'role' => $role,
                'target_entity' => $attrs['target_entity'] ?? 'System',
                'entity_id' => $attrs['entity_id'] ?? 0,
                'role_status' => 'Active',
            ]);
        }

        return $user;
    }
}
