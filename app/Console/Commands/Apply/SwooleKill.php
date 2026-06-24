<?php

namespace App\Console\Commands\Apply;

use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Services\OrderService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SwooleKill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swooleKill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除swoole进程';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $shell = "sh " . base_path('swooleKill.sh');
        exec($shell . " 2>&1", $res, $state);
        Log::error("-------killSwoole--------");
        Log::error($res);
    }
}
