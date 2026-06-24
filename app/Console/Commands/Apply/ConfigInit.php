<?php

namespace App\Console\Commands\Apply;

use App\Models\Order\OrderIndex;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConfigInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apply:ConfigInit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '店铺配置初始化';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
    }
}
