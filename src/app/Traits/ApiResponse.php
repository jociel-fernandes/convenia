<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

trait ApiResponse
{
    /**
     * Success response
     */
    public static function success($data = null, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message ?? __('api.general.success'),
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error(?string $message = null, $errors = null, int $statusCode = 400, ?string $code = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message ?? __('api.general.error'),
            'data' => null,
            'code' => $code,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Handle exceptions automatically and return appropriate JSON response
     */
    public static function handleException(Throwable $exception, ?string $fallbackMessage = null): JsonResponse
    {
        // Log the exception for debugging
        logger()->error('API Exception: '.$exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Handle specific exception types
        return match (true) {
            $exception instanceof ValidationException => self::validationError(
                $exception->getMessage(),
                $exception->errors()
            ),

            $exception instanceof \Illuminate\Auth\AuthenticationException => self::unauthorized(
                $exception->getMessage() ?: __('api.auth.unauthenticated')
            ),

            $exception instanceof \Illuminate\Auth\Access\AuthorizationException => self::forbidden(
                $exception->getMessage() ?: __('api.auth.forbidden')
            ),

            $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => self::notFound(
                __('api.general.resource_not_found')
            ),

            $exception instanceof \Illuminate\Database\QueryException => self::error(
                __('api.errors.database_error'),
                null,
                500,
                'DATABASE_ERROR'
            ),

            $exception instanceof \InvalidArgumentException => self::error(
                $exception->getMessage(),
                null,
                400,
                'INVALID_ARGUMENT'
            ),

            default => self::error(
                $fallbackMessage ?? __('api.errors.internal_server_error'),
                app()->environment('local') ? [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ] : null,
                500,
                'INTERNAL_ERROR'
            ),
        };
    }

    /**
     * Execute callback with automatic exception handling
     */
    public static function handle(callable $callback, ?string $successMessage = null): JsonResponse
    {
        try {
            $result = $callback();

            return match (true) {
                $result instanceof JsonResponse => $result,
                is_array($result) && isset($result['data']) => self::success($result['data'], $successMessage),
                default => self::success($result, $successMessage)
            };

        } catch (Throwable $exception) {
            return self::handleException($exception);
        }
    }

    /**
     * Create resource response - for store operations
     */
    public static function created(callable $callback, ?string $message = null): JsonResponse
    {
        try {
            $result = $callback();

            return self::success($result, $message ?? __('api.general.created'), 201);
        } catch (Throwable $exception) {
            return self::handleException($exception);
        }
    }

    /**
     * Update resource response - for update operations
     */
    public static function updated(callable $callback, ?string $message = null): JsonResponse
    {
        try {
            $result = $callback();

            return self::success($result, $message ?? __('api.general.updated'));
        } catch (Throwable $exception) {
            return self::handleException($exception);
        }
    }

    /**
     * Patch resource response - for partial updates that don't return data
     */
    public static function patched(callable $callback): JsonResponse
    {
        try {
            $callback();

            return response()->json(null, 204);
        } catch (Throwable $exception) {
            return self::handleException($exception);
        }
    }

    /**
     * Delete resource response - for destroy operations
     */
    public static function deleted(callable $callback, ?string $message = null): JsonResponse
    {
        try {
            $callback();

            return response()->json(null, 204);
        } catch (Throwable $exception) {
            return self::handleException($exception);
        }
    }

    /**
     * List resources response - for index operations
     */
    public static function listed(callable $callback, ?string $message = null): JsonResponse
    {
        return self::handle($callback, $message ?? __('api.general.retrieved'));
    }

    /**
     * Validation error response
     */
    public static function validationError(?string $message = null, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? __('api.errors.validation_error'),
            'data' => null,
            'code' => 'VALIDATION_ERROR',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Unauthorized response (401)
     */
    public static function unauthorized(?string $message = null, ?string $code = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? __('api.auth.unauthenticated'),
            'data' => null,
            'code' => $code ?? 'UNAUTHENTICATED',
        ], 401);
    }

    /**
     * Forbidden response (403)
     */
    public static function forbidden(?string $message = null, ?string $code = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? __('api.auth.forbidden'),
            'data' => null,
            'code' => $code ?? 'FORBIDDEN',
        ], 403);
    }

    /**
     * Not found response (404)
     */
    public static function notFound(?string $message = null, ?string $code = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message ?? __('api.general.resource_not_found'),
            'data' => null,
            'code' => $code ?? 'NOT_FOUND',
        ], 404);
    }

    /**
     * Paginated response
     */
    public static function paginated($paginatedData, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('api.general.success'),
            'data' => $paginatedData->items(),
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'last_page' => $paginatedData->lastPage(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
                'has_more_pages' => $paginatedData->hasMorePages(),
            ],
        ]);
    }
}
