<?php

namespace App\Support\V2;

/**
 * Two-way mapping between the database's human/Title-Case values and the
 * snake_case wire tokens the Flutter client expects (API_REFERENCE.md Appendix A
 * + per-section enum blocks).
 *
 * Decoding is lenient where the contract says so (unknown → sensible default);
 * encoding always emits a documented wire value. Nothing here touches the DB or
 * shared models — it is pure translation used by the v2 presentation layer.
 */
class WireEnums
{
    /** DB role string → wire role token (Appendix A: UserRole). */
    public const ROLE_TO_WIRE = [
        'Governor' => 'governor',
        'Coordinator' => 'coordinator',
        'Deputy Coordinator' => 'deputy_coordinator',
        'Sector Head' => 'sector_head',
        'Data Admin' => 'data_admin',
        'Facilitator' => 'facilitator',
        'System Admin' => 'system_admin',
    ];

    /** DB confirmation_status → wire lifecycle state (§11.6). */
    public const STATUS_TO_WIRE = [
        'Not Confirmed' => 'pending_entry',
        'Pending Sector Head Approval' => 'pending_sector_head',
        'Pending Facilitator' => 'pending_facilitator',
        'Pending Coordinator' => 'pending_coordinator',
        'Confirmed' => 'confirmed',
        'Rejected' => 'rejected',
    ];

    public static function roleToWire(?string $dbRole): ?string
    {
        if ($dbRole === null) {
            return null; // role may be null → client routes to a role picker (§11.1)
        }

        return self::ROLE_TO_WIRE[$dbRole] ?? null;
    }

    public static function wireToRole(?string $wire): ?string
    {
        if ($wire === null) {
            return null;
        }

        return array_search($wire, self::ROLE_TO_WIRE, true) ?: null;
    }

    public static function statusToWire(?string $dbStatus): string
    {
        return self::STATUS_TO_WIRE[$dbStatus] ?? 'pending_entry';
    }

    public static function wireToStatus(?string $wire): ?string
    {
        if ($wire === null) {
            return null;
        }

        return array_search($wire, self::STATUS_TO_WIRE, true) ?: null;
    }

    /** Quarter int (1–4) → wire token (`q1`–`q4`). */
    public static function quarterToWire(int|string|null $quarter): ?string
    {
        if ($quarter === null || $quarter === '') {
            return null;
        }

        $n = (int) $quarter;

        return ($n >= 1 && $n <= 4) ? 'q'.$n : null;
    }

    /** Wire token (`q1`–`q4`) → quarter int (1–4). */
    public static function wireToQuarter(?string $wire): ?int
    {
        if ($wire === null) {
            return null;
        }

        if (preg_match('/^q([1-4])$/', strtolower($wire), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * DB commitment status → wire status (§11.3): on_track / delayed / critical.
     * DB values are Title Case ('Completed', 'In Progress', 'At Risk',
     * 'Not Started') or the legacy default 'active'.
     */
    public static function commitmentStatusToWire(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'at risk', 'at_risk', 'critical' => 'critical',
            'not started', 'not_started', 'delayed' => 'delayed',
            default => 'on_track', // completed, in progress, active, …
        };
    }

    /** DB deliverable status → wire status (§11.3): active / delayed. */
    public static function deliverableStatusToWire(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'at risk', 'at_risk', 'delayed', 'critical' => 'delayed',
            default => 'active',
        };
    }
}
