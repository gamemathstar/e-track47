<?php

namespace App\Support\V2;

use App\Exceptions\V2\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Central response/error formatter for the v2 mobile API.
 *
 * Success responses are deliberately *raw* (object or array, no envelope) to match
 * the Flutter client contract (API_REFERENCE.md §5). Error responses use the
 * forward-compatible shape recommended in §6: { code, message, fieldErrors? }.
 *
 * This class is only invoked from the v2 lane (route-scoped in the exception
 * handler and from V2 controllers), so it never alters web or v1 responses.
 */
class ApiResponse
{
    /**
     * 204 No Content — the preferred success shape for void mutations (§7).
     */
    public static function noContent(): Response
    {
        return response()->noContent();
    }

    /**
     * 202 Accepted — for queued/command endpoints the client treats as success.
     * Emits a truly empty body when no payload is supplied (the client ignores it).
     */
    public static function accepted(array $body = []): JsonResponse|Response
    {
        return $body === [] ? response()->noContent(202) : new JsonResponse($body, 202);
    }

    /**
     * Structured error body. `fieldErrors` is only attached for validation failures.
     */
    public static function error(string $code, string $message, int $status, ?array $fieldErrors = null): JsonResponse
    {
        $body = ['code' => $code, 'message' => $message];

        if ($fieldErrors !== null) {
            $body['fieldErrors'] = $fieldErrors;
        }

        return new JsonResponse($body, $status);
    }

    /**
     * Map any throwable to the v2 error contract. Called from the route-scoped
     * renderable in App\Exceptions\Handler (only for `api/v2/*` requests).
     */
    public static function exception(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return self::error(
                'validation_error',
                'The given data was invalid.',
                422,
                self::flattenValidationErrors($e),
            );
        }

        if ($e instanceof ApiException) {
            return self::error($e->getErrorCode(), $e->getMessage(), $e->getStatus(), $e->getFieldErrors());
        }

        if ($e instanceof AuthenticationException) {
            return self::error('unauthenticated', 'Authentication is required.', 401);
        }

        if ($e instanceof AuthorizationException) {
            return self::error('forbidden', $e->getMessage() ?: 'This action is unauthorized.', 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return self::error('not_found', 'The requested resource was not found.', 404);
        }

        if ($e instanceof HttpExceptionInterface) {
            // Use a stable per-status message rather than echoing the framework's
            // internal text (which can leak the route path). Domain errors carry
            // their own message via ApiException, handled above.
            $status = $e->getStatusCode();

            return self::error(self::codeForStatus($status), self::messageForStatus($status), $status);
        }

        // Unexpected: 500. Surface the real message only when debugging.
        $message = config('app.debug') ? $e->getMessage() : 'An unexpected server error occurred.';

        return self::error('server_error', $message, 500);
    }

    /**
     * Reduce Laravel's field => [messages] map to field => first-message, per §6.
     */
    protected static function flattenValidationErrors(ValidationException $e): array
    {
        return array_map(
            static fn ($messages) => is_array($messages) ? ($messages[0] ?? '') : (string) $messages,
            $e->errors(),
        );
    }

    protected static function codeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            409 => 'conflict',
            422 => 'validation_error',
            429 => 'too_many_requests',
            default => $status >= 500 ? 'server_error' : 'error',
        };
    }

    protected static function messageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'The request was malformed.',
            401 => 'Authentication is required.',
            403 => 'This action is unauthorized.',
            404 => 'The requested resource was not found.',
            405 => 'The HTTP method is not allowed for this route.',
            409 => 'The request conflicts with the current state.',
            429 => 'Too many requests.',
            default => $status >= 500 ? 'An unexpected server error occurred.' : 'Request could not be completed.',
        };
    }
}
