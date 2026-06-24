<?php

namespace App\Http\Middleware\ChannelApi;

use Illuminate\Http\Request;
use Closure;
use App\Models\Visit;

class VisitMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
            $routeAction = $request->route()->getAction();
            $route = $routeAction['as'];
            if ($routeAction['prefix'] == 'channelApi') {
                if (in_array($route, ['index', 'profix', 'order.index'])) {
                    $uniacid = $request->header('uniacid');
                    $visitModel = new Visit();
                    $visitModel->userId = 0;
                    $visitModel->uniacid = $uniacid ?: 0;
                    $visitModel->type = 1;
                    $visitModel->model = $routeAction['prefix'];
                    $visitModel->route = $route;
                    $visitModel->method = $request->method();
                    $visitModel->ip = $request->ip();
                    $visitModel->post_str = '';
                    $visitModel->save();   # 记录日志
                }
            }
            return  $response;
        } catch (\Exception $e) {
            return  $response;
        }
    }
}
