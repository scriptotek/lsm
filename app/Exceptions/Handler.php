<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($this->shouldReport($e)) {
            app('sentry')->captureException($e);
        }
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return JsonResponse
     */
    public function render($request, Exception $e)
    {
        $message = $e->getMessage();
        if (is_object($message)) {
            $message = $message->toArray();
        }
        $code = 500;
        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();
        }
        if ($e instanceof AuthorizationException) {
            $code = 401;
            $message = 'Unauthorized.';
        }
        if ($e instanceof ModelNotFoundException) {
            $code = 404;
            $message = 'No such ' . $e->getModel() . ' found.';
        }
        return new JsonResponse([
            'error' => [
                'message' => $message,
                'code' => $code
            ]
        ], $code);
    }
}
