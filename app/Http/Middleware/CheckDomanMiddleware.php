<?php

namespace App\Http\Middleware;

use App\Models\Install;
use Closure;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CheckDomanMiddleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function handle(Request $request, Closure $next)
    {
        // 注释掉所有验证逻辑
        /*
        if (request()->is("api/*")) {
            $md5 = Install::where("type", 'md5')->first();
            $file_name = public_path() . '/' . "admin.lic";
            if ($md5->data !== md5_file($file_name)) {
                throw new BadRequestHttpException('授权文件异常,系统无法正常运行;请联系官方客服：15307193890（微信同号）');
            }
        }
        $data = getSysInfo();
        if ($data['status'] == 3) {
            throw new BadRequestHttpException('当前站点已被拉黑，系统无法正常运行;请联系官方客服：15307193890（微信同号）');
        }
        */
        return $next($request);
    }
}
