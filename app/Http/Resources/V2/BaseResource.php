<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base for all v2 API Resources.
 *
 * `$wrap = null` disables Laravel's default `{ "data": … }` envelope for this
 * class and every subclass, so v2 emits raw objects/arrays per API_REFERENCE.md
 * §5. This is scoped to v2 resources only — it does not call the global
 * JsonResource::withoutWrapping(), so v1 / web are unaffected (v1 builds arrays
 * manually and uses no Resources).
 */
abstract class BaseResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Convenience: drop null-valued keys so "omitted when null" optional fields
     * (used throughout the contract) don't serialize as explicit nulls when a
     * resource chooses to prune them. Opt-in via `static::pruneNulls($array)`.
     */
    protected static function pruneNulls(array $data): array
    {
        return array_filter($data, static fn ($v) => $v !== null);
    }
}
