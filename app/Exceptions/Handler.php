<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \App\Projects\Quickreads\Api\V2\ApiException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'identity_token', 'authorization_code', 'payload', 'refresh_token', 'client_secret',
    ];

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/v2', 'api/v2/*')) {
            return \App\Projects\Quickreads\Api\V2\Responses::error($exception);
        }

        return parent::render($request, $exception);
    }

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function report(Throwable $exception)
    {
        $request = app('request');
        if ($request->is('api/v2/auth/apple*', 'api/v2/story-activity') || ($request->isMethod('DELETE') && $request->is('api/v2/me'))) {
            if (!$this->shouldntReport($exception)) {
                // Error reporters may capture whole requests/exception arguments.
                // These endpoints contain credentials or private reading state.
                \Illuminate\Support\Facades\Log::error('QuickReads sensitive API operation failed.', [
                    'exception_type' => get_class($exception),
                ]);
            }
            return;
        }
        parent::report($exception);
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    // public function report(Exception $exception)
    // {
    //     parent::report($exception);
    // }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    // public function render($request, Exception $exception)
    // {
    //     return parent::render($request, $exception);
    // }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $guard = array_get($exception->guards(), 0);

        switch ($guard) {
            case 'quickreads-admin':
                $login = 'quickreads.admin.login';
                break;

            case 'videouploader-admin':
                $login = 'videouploader.login';
                break;
            
            default:
                $login = 'login';
                break;
        }

        return $request->expectsJson()
                    ? response()->json(['message' => $exception->getMessage()], 401)
                    : redirect()->guest(route($login));
    }
}
