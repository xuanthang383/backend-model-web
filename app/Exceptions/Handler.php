<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

// Sửa lại import ở đây

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
    }

    public function render($request, Throwable $e): Response|JsonResponse|\Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        if ($request->expectsJson()) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'r' => 1, // Mã lỗi của bạn
                    'msg' => 'Validation error',
                    'errors' => $e->errors(),
                    'data' => null,
                ], 500);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'r' => 1,
                    'msg' => 'Resource not found'
                ], 404);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'r' => 1,
                    'msg' => 'Invalid API route'
                ], 404);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'r' => 1,
                    'msg' => 'Unauthorized - Token không hợp lệ hoặc đã hết hạn',
                    'data' => null
                ], 401);
            }
        }

        return parent::render($request, $e);
    }
}
