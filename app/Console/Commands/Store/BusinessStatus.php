<?php

namespace App\Console\Commands\Store;

use App\Models\Admin\Apply;
use App\Models\StatisticsDay;
use App\Models\Store;
use Illuminate\Console\Command;

class BusinessStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:businessStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '门店营业状态更新';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
        return true;
        $list  = Store::select(["id",'businessStatus','businessData','uniacid'])->withTrashed()->get();
        foreach ($list as $key => $v) {
            
        }
    }
}
