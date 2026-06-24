<?php

namespace App\Processes;

use App\Tasks\TestTask;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Artisan;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Process;

class QueueProcess implements CustomProcessInterface
{
    /**
     * @var bool 退出标记，用于Reload更新
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        // 进程运行的代码，不能退出，一旦退出Manager进程会自动再次创建该进程。
        Artisan::call('queue:listen');
    }
    // 要求：LaravelS >= v3.4.0 并且 callback() 必须是异步非阻塞程序。
    public static function onReload(Server $swoole, Process $process)
    {
        // Stop the process...
        // Then end process
        echo ('queue process: reloading');
        self::$quit = true;
        // $process->exit(0); // 强制退出进程
    }
    // 要求：LaravelS >= v3.7.4 并且 callback() 必须是异步非阻塞程序。
    public static function onStop(Server $swoole, Process $process)
    {
        // Stop the process...
        // Then end process
        echo ('queue process: stopping');
        self::$quit = true;
        // $process->exit(0); // 强制退出进程
    }
}
