<?php

namespace App\Http\Middleware\Admin;

use App\Http\Helpers\ApiResponse;
use App\Models\Admin;
use App\Models\Role;
use App\Models\RolePermission;
use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Traits\ResourceTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


config(['auth.defaults.guard' => 'admin']);
class RefreshTokenAdmin extends BaseMiddleware
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
        Auth::shouldUse('admin');
        config(['auth.defaults.guard' => 'admin']);

        // 检查此次请求中是否带有 token，如果没有则抛出异常。
        try {
            $this->checkForToken($request);
        } catch (UnauthorizedHttpException $e) {
            return $this->failed('登录异常,请重新登录', 401);
        }
        // 如果Token不能解析的情况下
        try {
            // 使用 try 包裹，以捕捉 token 过期所抛出的 TokenExpiredException  异常
            try {
                // 检测用户的登录状态，如果正常则通过
                // dd($this->auth->parseToken()->authenticate());
                $guard = Auth::guard('admin');
                if (method_exists($guard, 'setRequest')) {
                    $guard->setRequest($request);
                }

                if ($userInfo = $guard->user()) {
                    // 是否有权限返回
                    if (!$this->getPermission($userInfo)) {
                        return $this->failed('登录异常,请重新登录', 400);
                    }
                    return $next($request);
                }
                return $this->failed('登录异常,请重新登录', 401);
                // throw new UnauthorizedHttpException('jwt-auth', '未登录');
            } catch (TokenExpiredException $exception) {
                // 此处捕获到了 token 过期所抛出的 TokenExpiredException 异常，我们在这里需要做的是刷新该用户的 token 并将它添加到响应头中
                return $this->failed('登录异常,请重新登录', 401);
            }
        } catch (TokenInvalidException $e) {
            return $this->failed('登录异常,请重新登录', 401);
        }


        // 在响应头中返回新的 token
        //return $this->setAuthenticationHeader($next($request), $token);
        return $next($request);
    }


    // 判断是否有权限
    protected function getPermission($userInfo)
    {
        $id = $userInfo->id;
        $as = request()->route()->getAction()['as'] ?? 'not_name';
        if (in_array($as, ['changePassword']) && env('APP_ENV', 'debug') == 'yanshi') {
            return false;
        }
        if (in_array($as, ['updateSystem']) && env('APP_ENV', 'debug') == 'ceshi') {
            return false;
        }
        return true;
        $admin = Admin::find($id)->toArray();
        if ($admin['role_id'] == 0) {
            return true;
        }
        $admin_model = new Admin();
        $role_model = new Role();
        try {
            $res = RolePermission::whereHas('permission', function ($q) use ($as) {
                $q->where('apis', $as);
            })->where('role_id', $admin['role_id'])->first();
            return $res ? true : false;
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}
