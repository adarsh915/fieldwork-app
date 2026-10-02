<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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

        // Catch 403 / AuthorizationException for web routes:
        // redirect back with a toast flash instead of showing a 403 page.
        // Note: Spatie throws its own UnauthorizedException (different from Laravel's).
        $this->renderable(function (SpatieUnauthorizedException|AuthorizationException|AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return null; // let the API handle it normally
            }

            return redirect()->back()->withInput()->with(
                'permission_denied',
                'You do not have permission to perform this action.'
            );
        });
    }
}
