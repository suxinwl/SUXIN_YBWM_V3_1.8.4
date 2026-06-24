<?php

namespace App\Console\Commands\Member;

use App\Events\BirthdayGiftEvent;
use App\Models\Delivery\Order;
use App\Models\Member;
use App\Models\Order\OrderIndex;
use App\Models\TakeOut\Delivery;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Event;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BirthdayPack extends Command
{
    protected $signature = 'member:birthdayGift';

    /**
     * The console command description.
     
     * @var string
     */
    protected $description = '发放生日有礼';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $configList = DB::table('channel_config')->where('ident', 'birthdayGift')->get();
        collect($configList)->each(function ($config) {
            $data = json_decode($config->data, true);
            if ($data['birthday'] && $data['birthday']['switch'] == 1) {
                $m = Carbon::now()->addDays($data['birthday']['days'])->format('m');
                $d = Carbon::now()->addDays($data['birthday']['days'])->format('d');
                $members = Member::where('uniacid', $config->uniacid)->whereDoesntHave("birthdayGift")
                    ->whereRaw("DATE_FORMAT(`birthday`, '%m') = $m")
                    ->whereRaw("DATE_FORMAT(`birthday`, '%d') = $d")
                    ->limit(20);
                foreach ($members as $key => $v) {
                    Event(new BirthdayGiftEvent($v, 'birthday'));
                }
            }
        });
    }
}
