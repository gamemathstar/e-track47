<?php

namespace App\Http\Resources\V2;

use App\Support\V2\WireEnums;
use Illuminate\Http\Request;

/**
 * The compact "User object" shared by login and GET /auth/me (API_REFERENCE.md
 * §11.1). Wraps an App\Models\User.
 *
 * Field mapping: id→string PK, name→full_name, role→wire role (snake_case or null
 * when unassigned, so the client routes to a role picker), mustChangePassword→bool.
 */
class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $role = $this->getCurrentRole();

        return [
            'id' => (string) $this->id,
            'email' => $this->email,
            'name' => $this->full_name,
            'role' => WireEnums::roleToWire($role?->role),
            'mustChangePassword' => (bool) ($this->must_change_password ?? false),
        ];
    }
}
