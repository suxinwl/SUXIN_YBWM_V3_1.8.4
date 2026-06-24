<?php

namespace App\Events;

use Hhxsv5\LaravelS\Swoole\Events\WorkerStartInterface;
use Illuminate\Support\Facades\Redis;
use Swoole\Http\Server;

class WorkerStartEvent implements WorkerStartInterface
{
    public function __construct()
    {
    }

    public function handle(Server $server, $workerId)
    {
        $configprefix = config('database.redis.options.prefix');
        $keys = Redis::keys("wsTable:*");
        foreach ($keys as $key) {
            Redis::del(str_replace($configprefix, '', $key));
        }
    }
}
