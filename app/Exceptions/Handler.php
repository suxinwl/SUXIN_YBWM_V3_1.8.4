<?php

namespace App\Exceptions;

use App\Http\Helpers\ApiResponse;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use  Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    use ApiResponse;
    public $doReport = [
        AuthenticationException::class => ['未登录或登录状态失效', 401],
        ModelNotFoundException::class => ['该模型未找到', 404],
        AuthorizationException::class => ['没有此权限', 403],
        ValidationException::class => [],
        UnauthorizedHttpException::class => ['未登录或登录状态失效', 401],
        TokenInvalidException::class => ['未登录或登录状态失效', 401],
        NotFoundHttpException::class => ['没有找到该页面', 404],
        MethodNotAllowedHttpException::class => ['访问方式不正确', 405],
        ThrottleRequestsException::class => ['操作频繁', 400],
       // Exception::class => ['服务器内部错误', 500],
        //QueryException::class => ['参数错误', 400],
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return $this->failed(current($e->errors()), $e->status);
        }
        $e = $this->prepareException($this->mapException($e));
        Log::info($e->getMessage());
        foreach (array_keys($this->doReport) as $report) {
            if ($e instanceof $report) {
                $message = $this->doReport[$report];
                Log::info($message);
                return $this->failed($message[0], $message[1]);
            }
        }
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            return $this->failed($e->getMessage(), 401);
        }
        return $this->failed($e->getMessage());
    }
}
