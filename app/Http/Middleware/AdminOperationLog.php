<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin\HandleLog;

class AdminOperationLog
{
    public function handle(Request $request, Closure $next)
    {
       
        $response = $next($request);
        return  $response;
        if ($response->getStatusCode() == 200 && in_array($request->method(), ['POST', 'PUT', 'DELETE', 'HEAD'])) {
            $input = $request->all();
            $authorization = $request->header('authorization');

            $route = $request->path();
            $channel =  explode('/', $request->path())[0];
            if ($channel == 'api' || $channel == 'channel') {
                $user = auth('admin')->user();
                $username = empty($user) ? '' : $user->username;
            } elseif ($channel == 'channelApi') {
                $user = auth('user')->user();
                $username = empty($user) ? '' : $user->nickname;
            } else {
                $username = '';
            }

            try {
                if ($authorization && ($channel == 'api' || $channel == 'channel')) {
                    $user = auth('admin')->user();
                    $uniacid = intval($request->header('uniacid'));
                    $userId = $user->id;
                    $type = 2;
                    $log = new HandleLog();
                    $log->username = $username;
                    $log->userId = intval($userId);
                    $log->uniacid = $uniacid ?: 0;
                    $log->type = $type;
                    $log->channel = $channel;
                    $log->route = $route;
                    $log->method = $request->method();
                    $log->ip = $request->ip();
                    $log->input = json_encode($input, 320);
                    $log->created_at = date('Y-m-d H:i:s', time());
                    $log->save();   # 记录日志
                }
            } catch (\Exception $e) {
                
            }
        }
        return  $response;
    }
}
