<?php

namespace App\Http\Resources\V2;

use App\Support\V2\WireEnums;
use Illuminate\Http\Request;

/**
 * The full profile object for GET /profile/me (API_REFERENCE.md §11.2).
 *
 * Several fields have no column in the current `users` table; they are derived
 * where sensible (joinDate ← created_at, department ← assigned sector, avatarUrl
 * ← image_url) and otherwise pruned (omitted) rather than sent as null. Required
 * fields (id, fullName, email) are always present.
 */
class ProfileResource extends BaseResource
{
    /** Deterministic institutional label (no column for it yet — see B3). */
    private const ORGANIZATION = 'Jigawa State Government';

    public function toArray(Request $request): array
    {
        $role = $this->getCurrentRole();

        return static::pruneNulls([
            'id' => (string) $this->id,
            'fullName' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone_number ?: null,
            'role' => WireEnums::roleToWire($role?->role),
            'organization' => self::ORGANIZATION,
            'department' => $this->resolveSectorName(),
            'address' => null,
            'staffId' => null,
            'joinDate' => optional($this->created_at)->format('Y-m-d'),
            'bio' => null,
            'avatarUrl' => $this->resolveAvatarUrl(),
        ]);
    }

    /** Sector name when the user is bound to a single sector, else null. */
    private function resolveSectorName(): ?string
    {
        $sector = $this->isSectorHead() ?: $this->isDataAdmin();

        return $sector ? $sector->sector_name : null;
    }

    private function resolveAvatarUrl(): ?string
    {
        return $this->image_url ? asset('uploads/users/'.$this->image_url) : null;
    }
}
