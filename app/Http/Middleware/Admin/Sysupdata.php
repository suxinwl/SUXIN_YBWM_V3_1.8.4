<?php

namespace App\Http\Middleware\Admin;

use App\Http\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use App\Models\Admin\HandleLog;
use App\Traits\ResourceTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Sysupdata
{
    use ApiResponse;
    public function handle(Request $request, Closure $next)
    {
        // 注释掉所有验证逻辑
        /*
        try {
            $res = httpRequest(config('app.authorizeDomain') . '/cloud/auth/checkDomain', ['domain_url' => Request()->server('HTTP_HOST')]);
            if (empty($res) || $res['code'] != 1) {
                return $this->failed($res['msg']);
            }
            checkDomain();
        } catch (\Exception $e) {
            throw new BadRequestHttpException('您的站点已到期，详情请联系速信客服：18038018206（微信同号）');
        }
        */
        return $next($request);
    }
}
