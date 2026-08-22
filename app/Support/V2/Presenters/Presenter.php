<?php

namespace App\Support\V2\Presenters;

use Carbon\Carbon;

/**
 * Base class for the v2 presentation layer. Feature presenters extend this to
 * derive the pre-formatted display fields the client expects (labels, accents,
 * icon keys, relative times, currency, initials) — see API_REFERENCE.md §3/§6.
 *
 * Presenters are pure functions over model data; they never mutate models
 * (guardrail GR1). Keep deterministic so the same input always yields the same
 * wire output.
 */
abstract class Presenter
{
    /** Fixed accent slot vocabulary (A4) shared across features. */
    public const ACCENTS = ['primary', 'secondary', 'tertiary', 'error', 'performance_fair'];

    /** Stringify an integer primary key for the wire (A2). */
    public static function id(int|string|null $id): ?string
    {
        return $id === null ? null : (string) $id;
    }

    /** Relative-time label, e.g. "2h ago", "3d ago", "Updated just now". */
    public static function relativeTime(Carbon|string|null $when, ?string $prefix = null): ?string
    {
        if ($when === null) {
            return null;
        }

        $dt = $when instanceof Carbon ? $when : Carbon::parse($when);
        $label = $dt->diffForHumans(['short' => true]);

        return $prefix ? trim($prefix.' '.$label) : $label;
    }

    /** Avatar fallback initials from a display name, e.g. "Amina Egbe" → "AE". */
    public static function initials(?string $name, int $max = 2): string
    {
        if (! $name) {
            return '';
        }

        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = array_map(static fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), $parts);

        return implode('', array_slice(array_filter($letters), 0, $max));
    }

    /** Naira currency display string, abbreviated for compact tiles only. */
    public static function money(float|int|string|null $amount, bool $abbreviate = false): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $value = (float) $amount;

        if ($abbreviate) {
            if ($value >= 1_000_000_000) {
                return '₦'.rtrim(rtrim(number_format($value / 1_000_000_000, 1), '0'), '.').'B';
            }
            if ($value >= 1_000_000) {
                return '₦'.rtrim(rtrim(number_format($value / 1_000_000, 1), '0'), '.').'M';
            }
        }

        return '₦'.number_format($value, 0);
    }

    /** Clamp a raw percentage to a 0–1 fraction (double on the wire). */
    public static function fraction(float|int|string|null $percent): float
    {
        $p = (float) $percent;

        return max(0.0, min(1.0, $p / 100));
    }

    /**
     * Performance % with the project's 101% cap applied before display/aggregation
     * (matches the web app's reporting rule). Returns a rounded number.
     */
    public static function cappedPercent(float|int|string|null $percent): float
    {
        $p = (float) $percent;

        return round(min($p, 101), 1);
    }
}
