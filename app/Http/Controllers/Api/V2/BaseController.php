<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Support\V2\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Base for all v2 API controllers. Controllers stay thin: they validate (via
 * FormRequests), delegate to Services, and return Resources or one of the
 * command helpers below. Responses are raw (no envelope) per API_REFERENCE.md §5.
 */
abstract class BaseController extends Controller
{
    /** 204 for void mutations (the contract's preferred command success). */
    protected function noContent(): Response
    {
        return ApiResponse::noContent();
    }

    /** 202 for queued command endpoints (submit/review/etc.). */
    protected function accepted(array $body = []): JsonResponse|Response
    {
        return ApiResponse::accepted($body);
    }
}
