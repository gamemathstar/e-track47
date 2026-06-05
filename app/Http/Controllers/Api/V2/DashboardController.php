<?php

namespace App\Http\Controllers\Api\V2;

use App\Services\V2\DashboardService;
use Illuminate\Http\Request;

/**
 * Role dashboards (API_REFERENCE.md §11.11). Each endpoint is resolved from the
 * authenticated user and gated to its role (403 otherwise). Responses are raw
 * computed snapshot objects.
 */
class DashboardController extends BaseController
{
    public function __construct(private readonly DashboardService $dashboards)
    {
    }

    public function governor(Request $request): array
    {
        return $this->dashboards->governor($request->user());
    }

    public function coordinator(Request $request): array
    {
        return $this->dashboards->coordinator($request->user());
    }

    public function facilitator(Request $request): array
    {
        return $this->dashboards->facilitator($request->user());
    }

    public function sectorHead(Request $request): array
    {
        return $this->dashboards->sectorHead($request->user());
    }

    public function dataAdmin(Request $request): array
    {
        $validated = $request->validate([
            'quarter' => ['nullable', 'in:q1,q2,q3,q4'],
        ]);

        return $this->dashboards->dataAdmin($request->user(), $validated['quarter'] ?? null);
    }

    public function systemAdmin(Request $request): array
    {
        return $this->dashboards->systemAdmin($request->user());
    }
}
