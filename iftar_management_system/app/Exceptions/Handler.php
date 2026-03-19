<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Only handle API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException($request, Throwable $e)
    {
        // Validation Exception
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                $e->errors(),
                'Ralat pengesahan. Sila semak input anda.'
            );
        }

        // Model Not Found Exception
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return ApiResponse::notFound("Rekod {$model} tidak dijumpai.");
        }

        // Not Found HTTP Exception
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::notFound('Endpoint tidak dijumpai.');
        }

        // Method Not Allowed Exception
        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error(
                'Kaedah HTTP tidak dibenarkan untuk endpoint ini.',
                null,
                405
            );
        }

        // Authentication Exception
        if ($e instanceof AuthenticationException) {
            return ApiResponse::unauthorized('Pengesahan diperlukan. Sila log masuk.');
        }

        // Authorization Exception
        if ($e instanceof AuthorizationException) {
            return ApiResponse::forbidden('Anda tidak mempunyai kebenaran untuk tindakan ini.');
        }

        // Database Query Exception
        if ($e instanceof QueryException) {
            // Don't expose database errors in production
            if (config('app.debug')) {
                return ApiResponse::serverError(
                    'Ralat pangkalan data.',
                    [
                        'message' => $e->getMessage(),
                        'sql' => $e->getSql(),
                    ]
                );
            }
            return ApiResponse::serverError('Ralat pangkalan data. Sila cuba lagi.');
        }

        // Generic HTTP Exception
        if ($e instanceof HttpException) {
            return ApiResponse::error(
                $e->getMessage() ?: 'Ralat berlaku.',
                null,
                $e->getStatusCode()
            );
        }

        // Generic/Unexpected Exceptions
        if (config('app.debug')) {
            return ApiResponse::serverError(
                'Ralat sistem berlaku.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(5)->toArray(),
                ]
            );
        }

        // Production: Hide error details
        return ApiResponse::serverError('Ralat sistem berlaku. Sila cuba lagi.');
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::unauthorized('Pengesahan diperlukan. Sila log masuk.');
        }

        return redirect()->guest(route('login'));
    }
}
