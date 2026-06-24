<?php

namespace App\Http\Middleware\ChannelApi;

use App\Http\Helpers\ApiResponse;
use App\Models\Admin;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\StatisticsDay;
use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Traits\ResourceTrait;
use Illuminate\Support\Facades\Request;

config(['auth.defaults.guard' => 'user']);
class RefreshTokenApi extends BaseMiddleware
{
    use ApiResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 检查此次请求中是否带有 token，如果没有则抛出异常。
        try {

            $this->checkForToken($request);
        } catch (UnauthorizedHttpException $e) {
            return $this->failed(__('auth.no_token'), 401);
        }
        // 如果Token不能解析的情况下
        try {
            // 使用 try 包裹，以捕捉 token 过期所抛出的 TokenExpiredException  异常
            try {
                // 检测用户的登录状态，如果正常则通过
                // dd($this->auth->parseToken()->authenticate());
                if ($userInfo = $this->auth->parseToken()->authenticate()) {
                    if ($userInfo->state != 1) {
                        return $this->failed('该用户已被拉黑', 402);
                    }
                    // if (empty($userInfo->lastLogin) || $userInfo->lastLogin != date("Y-m-d", time())) {
                    //     $userInfo->lastLogin = date("Y-m-d", time());
                    //     $userInfo->save();
                    //     StatisticsDay::where('uniacid', Request()->header('uniacid'))->where('day', date("Y-m-d", time()))
                    //         ->increment('uv', 1);
                    // }
                    return $next($request);
                }
                return $this->failed(__('auth.no_token'), 401);
                // throw new UnauthorizedHttpException('jwt-auth', '未登录');
            } catch (TokenExpiredException $exception) {
                // 此处捕获到了 token 过期所抛出的 TokenExpiredException 异常，我们在这里需要做的是刷新该用户的 token 并将它添加到响应头中
                try {
                    // 刷新用户的 token
                    $token = $this->auth->refresh();
                    // 使用一次性登录以保证此次请求的成功
                    Auth::guard('admin')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
                } catch (JWTException $exception) {
                    // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
                    return $this->failed(__('auth.no_token'), 401);
                    // throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage());
                }
            }
        } catch (TokenInvalidException $e) {
            return $this->failed(__('auth.error_token'), 401);
        }


        // 在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);
        // return $next($request);
    }
}
