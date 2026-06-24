<?php

namespace App\Http\Middleware;

use App\Http\Helpers\ApiResponse;
use App\Models\Admin;
use App\Models\Admin\Apply;
use App\Models\Role;
use App\Models\RolePermission;
use App\Services\ConfigService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OnlyLoginMiddleware extends BaseMiddleware
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
        $config = ConfigService::getSystemSet('loginSwitch');
        if ($config->only_login_switch == 1) {
            $guardName = Str::startsWith($request->path(), 'channelApi') ? 'user' : 'admin';
            Auth::shouldUse($guardName);
            $guard = Auth::guard($guardName);
            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($request);
            }

            $userInfo = $guard->user();
            if (!$userInfo) {
                throw new AuthenticationException('Invalid login session');
            }
            $id = $userInfo->id;
            $key = 'login:Api:' . $id . ':pc:token';
            $token = $guard->getToken();
            $cacheToken = Cache::get($key);
            if ($token != $cacheToken) {
                throw new  AuthenticationException('您的账号已在别处登录');
            }
        }
        return $next($request);
    }
}
