<?php

namespace App\Exceptions;

use App\Support\V2\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // v2-only error contract. Scoped strictly to `api/v2/*` (GR3): returning
        // null for any other request leaves web HTML error pages and the v1 API
        // response path completely untouched.
        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/v2/*')) {
                return null;
            }

            return ApiResponse::exception($e);
        });
    }
}
