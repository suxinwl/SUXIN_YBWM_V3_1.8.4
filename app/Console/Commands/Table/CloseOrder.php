<?php

namespace App\Console\Commands\Table;

use App\Models\Admin\Apply;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Models\StoredValue;
use App\Models\Tables\Table;
use App\Services\InStoreOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:CloseOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function handle()
    {
    }
}
