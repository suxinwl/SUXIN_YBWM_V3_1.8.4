<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

/**
 * 多语言
 *
 * @author Wenhsing <wenhsing@qq.com>
 */
class LangMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @author Wenhsing <wenhsing@qq.com>
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 获取请求头中的语言
        $lang = $request->header('lang');
        // 获取请求地址参数中的语言
        if (empty($lang)) {
            $lang = $request->query('lang');
        }

        // 获取请求主体中的语言
        if (empty($lang)) {
            $lang = $request->input('lang');
        }
        if (empty($lang)) {
            $lang = "cn";
        }
        // 匹配语言，进行语言设置
        App::setLocale($lang);
        $response = $next($request);
        // 进行其他操作
        // 例如：设置请求的语言到响应
        return $response;
    }
}