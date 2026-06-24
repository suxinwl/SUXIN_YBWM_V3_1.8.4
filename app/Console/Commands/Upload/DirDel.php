<?php

namespace App\Console\Commands\Upload;

use App\Models\Admin\Apply;
use App\Models\Order\OrderIndex;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\Tables\Table;
use App\Services\BillService;
use App\Services\InStoreOrderService;
use App\Services\StaticService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DirDel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:dirdel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除小程序目录';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        $miniPath = storage_path('app/weixinOpen/ybwm_open');
        $shopPath = storage_path('app/merchant/ybv3_merchant');
        if (File::isDirectory($miniPath)) {
            File::deleteDirectory($miniPath);
        }
        if (File::isDirectory($shopPath)) {
            File::deleteDirectory($shopPath);
        }
    }
}
