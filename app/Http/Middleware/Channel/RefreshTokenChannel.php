<?php

namespace App\Http\Middleware\Channel;

use App\Http\Helpers\ApiResponse;
use App\Models\Admin;
use App\Models\Admin\Apply;
use App\Models\Role;
use App\Models\RolePermission;
use App\Services\ConfigService;
use App\Services\PlugService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Traits\ResourceTrait;
use EasyWeChat\Kernel\Support\Arr;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

config(['auth.defaults.guard' => 'admin']);
class RefreshTokenChannel extends BaseMiddleware
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
            return $this->failed("登录异常,请重新登录", 401);
        }
        // 如果Token不能解析的情况下
        try {
            // 使用 try 包裹，以捕捉 token 过期所抛出的 TokenExpiredException  异常
            try {
                // 检测用户的登录状态，如果正常则通过
                $guard = Auth::guard('admin');
                if (method_exists($guard, 'setRequest')) {
                    $guard->setRequest($request);
                }

                if ($userInfo = $guard->user()) {
                    // 是否有权限返回
                    if (!$this->getPermission($userInfo)) {
                        return $this->failed("您没有访问权限", 401);
                    }
                    return $next($request);
                }
                return $this->failed("登录异常,请重新登录", 401);
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
                    return $this->failed(__('登录异常,请重新登录'), 401);
                    // throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage());
                }
            }
        } catch (TokenInvalidException $e) {
            return $this->failed(__('登录异常,请重新登录'), 401);
        }


        // 在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);
        // return $next($request);
    }


    // 判断是否有权限
    protected function getPermission($userInfo)
    {
        if ($userInfo->role_id == 0) {
            return true;
        }
        if ($userInfo->status == 2) {
            throw new AuthenticationException('禁止访问');
        }
        if ($as = request()->route()->getAction()['as'] == 'logout') {
            return true;
        }
        $id = $userInfo->id;
        $uniacid = Request()->header('uniacid');
        $appType = Request()->header('appType', 'pc');
        if ($appType == 'callStation') {
            return true;
        }
        $apply = Apply::find($uniacid);
        $appids = Apply::where('createUserId', $id)->get();
        $ids = collect($appids)->pluck('id')->toArray();
        if ($userInfo->role->module == 'store') {
            if (!in_array(appType($appType), $userInfo->role->appList ?? [])) {
                throw new AuthenticationException('该软件暂未授权,请联系管理员');
            }
        }
        if ($userInfo->role_id != 0 && $userInfo->isAdmin == 0 && $userInfo->uniacid != $uniacid) {
            return false;
        }
        if ($uniacid) {
            if ($userInfo->uniacid == 0 && !in_array($uniacid, $ids)) {
                return false;
            }
            if ($apply->admin->status != 1) {
                throw new AuthenticationException('当前平台服务已被禁用，请联系管理员咨询');
            }

            if ($apply->status != 1 && $apply->masterId > 0) {
                throw new AuthenticationException('当前平台服务已到期或已被禁用，请联系管理员咨询');
            }
            if (strtotime($apply->endTime) < time() && $apply->masterId > 0) {
                throw new AuthenticationException('当前平台服务已到期，请联系管理员续费');
            }
            if (!$userInfo->isAdmin) {
                if ($appType == 'store' && !in_array('business', PlugService::applyPlug($uniacid))) {
                    throw new AuthenticationException('该软件暂未授权,请联系管理员');
                }
                if ($appType == 'cashier' && !in_array('cashier', PlugService::applyPlug($uniacid))) {
                    throw new AuthenticationException('该软件暂未授权,请联系管理员');
                }
            }
        }

        return true;
        if (!request()->is("api/channel/*")) {
            return false;
        }
        $admin = Admin::find($id)->toArray();
        if (empty($admin['role_id'])) {
            return true;
        }
        $admin_model = new Admin();
        $role_model = new Role();
        try {
            $res = RolePermission::whereHas('permission', function ($q) {
                $as = request()->route()->getAction()['as'] ?? 'not_name';
                $q->where('apis', $as);
            })->where('role_id', $admin['role_id'])->first();
            return $res ? true : false;
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}
