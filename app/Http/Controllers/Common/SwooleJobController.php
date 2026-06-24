<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocketMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SynUser;
use EasyWeChat\Factory;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SwooleJobController extends ApiController
{
    public function Index(Request $request)
    {
    }
    public function store(Request $request)
    {
        $className = $request->className;
        if (empty($className)) {
            throw new BadRequestException('缺少参数');
        }
        $job = new $className($request->jobData);
        $ret = Task::deliver($job);
        return $this->success($ret, 'success');
    }

    public function SendMessage(SocketMessageRequest $request)
    {
        Log::error('socket:messagList');
        $server = app('swoole');
        if (!empty($request->storeId)) {
            $storeList = Redis::smembers('wsTable:store:' . $request->storeId);
            Log::error('socket:messagList');
            Log::error($storeList);
            foreach ($storeList  as $key => $fd) {
                $server->push($fd, $request->message);
            }
        }
        return $this->success([], 'success');
    }
}
