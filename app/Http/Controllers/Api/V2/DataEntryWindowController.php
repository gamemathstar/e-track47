<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\DataEntry\GrantOverrideRequest;
use App\Services\V2\DataEntryWindowService;
use Illuminate\Http\Request;

/**
 * Data-entry windows (API_REFERENCE.md §11.7). Coordinator only.
 */
class DataEntryWindowController extends BaseController
{
    public function __construct(private readonly DataEntryWindowService $windows)
    {
    }

    /** GET /data-entry/windows */
    public function index(Request $request): array
    {
        return $this->windows->listWindows(
            $request->user(),
            $request->query('year') !== null ? (int) $request->query('year') : null,
            $request->query('quarter'),
        );
    }

    /** GET /data-entry/stats */
    public function stats(Request $request): array
    {
        return $this->windows->stats(
            $request->user(),
            $request->query('year') !== null ? (int) $request->query('year') : null,
            $request->query('quarter'),
        );
    }

    public function lockAll(Request $request)
    {
        $this->windows->lockAll($request->user());

        return $this->accepted();
    }

    public function unlockAll(Request $request)
    {
        $this->windows->unlockAll($request->user());

        return $this->accepted();
    }

    public function open(Request $request, string $sectorId)
    {
        $this->windows->open($request->user(), $sectorId);

        return $this->accepted();
    }

    public function lock(Request $request, string $sectorId)
    {
        $this->windows->lock($request->user(), $sectorId);

        return $this->accepted();
    }

    public function override(GrantOverrideRequest $request, string $sectorId)
    {
        $this->windows->grantOverride($request->user(), $sectorId, $request->validated());

        return $this->accepted();
    }
}
