<?php

namespace App\Support\V2\Presenters;

use App\Models\Sector;

/**
 * Derives the client-facing display slots a Sector needs but the DB doesn't store
 * (API_REFERENCE.md §11.3) — chiefly the `icon` token. Deterministic keyword
 * mapping over the sector name/ministry; falls back to a generic icon.
 */
class SectorPresenter extends Presenter
{
    public static function icon(Sector $sector): string
    {
        $haystack = strtolower(trim(($sector->sector_name ?? '').' '.($sector->ministry ?? '')));

        return match (true) {
            str_contains($haystack, 'health') => 'medical_services',
            str_contains($haystack, 'educat') || str_contains($haystack, 'school') => 'school',
            str_contains($haystack, 'agric') => 'agriculture',
            str_contains($haystack, 'power') || str_contains($haystack, 'energy') || str_contains($haystack, 'electr') => 'bolt',
            str_contains($haystack, 'water') || str_contains($haystack, 'sanit') => 'water_drop',
            str_contains($haystack, 'road') || str_contains($haystack, 'infra') || str_contains($haystack, 'works') || str_contains($haystack, 'transport') => 'construction',
            str_contains($haystack, 'secur') || str_contains($haystack, 'police') => 'security',
            str_contains($haystack, 'financ') || str_contains($haystack, 'budget') || str_contains($haystack, 'economic') => 'account_balance',
            str_contains($haystack, 'women') || str_contains($haystack, 'social') => 'diversity_3',
            default => 'dashboard',
        };
    }

    /** Deterministic accent slot from a stable id (for sectors that need one). */
    public static function accent(int|string $id): string
    {
        $slots = ['primary', 'secondary', 'tertiary', 'error'];

        return $slots[(int) abs(crc32((string) $id)) % count($slots)];
    }
}
