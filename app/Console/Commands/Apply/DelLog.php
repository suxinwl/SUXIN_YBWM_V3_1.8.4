<?php

namespace App\Console\Commands\Apply;

use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Services\OrderService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DelLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delLog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除日志文件';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $res = Storage::disk('log')->delete('laravel.log');
        Storage::disk('log')->put('laravel.log','');
        chmod(storage_path('logs/laravel.log'),0777);
    }
}
