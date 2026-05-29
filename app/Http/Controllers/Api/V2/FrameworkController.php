<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Frameworks\CreateFrameworkRequest;
use App\Services\V2\FrameworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Framework management (API_REFERENCE.md §11.5). Read endpoints are accessible to
 * any authenticated user (no role gate documented); create/archive/set-default
 * are coordinator-only (enforced inside the service).
 */
class FrameworkController extends BaseController
{
    public function __construct(private readonly FrameworkService $frameworks)
    {
    }

    public function index(Request $request): array
    {
        return $this->frameworks->list();
    }

    public function stats(Request $request): array
    {
        return $this->frameworks->stats();
    }

    public function show(Request $request, string $id): array
    {
        return $this->frameworks->get($id);
    }

    public function sectors(Request $request, string $id): array
    {
        return $this->frameworks->sectorsFor($id);
    }

    public function store(CreateFrameworkRequest $request): JsonResponse
    {
        $framework = $this->frameworks->create($request->user(), $request->validated());

        return response()->json($framework, 201);
    }

    public function archive(Request $request, string $id)
    {
        $this->frameworks->archive($request->user(), $id);

        return $this->accepted();
    }

    public function setDefault(Request $request, string $id)
    {
        $this->frameworks->setDefault($request->user(), $id);

        return $this->accepted();
    }
}
