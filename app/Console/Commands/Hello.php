<?php

namespace App\Console\Commands;

use App\Console\Commands\Order\Bill;
use App\Events\EquityCardEvent;
use App\Events\PartnerEvent;
use App\Events\PartyBEvent;
use App\Events\StoreMessageEvent;
use App\Jobs\Order\CloseExpiredOrderJob;
use App\Jobs\OrderStatisticsJob;
use App\Listeners\Message\MiniMessageListener;
use App\Models\EquityCard\Member;
use App\Models\GoodsSku;
use App\Models\InStore\Order\Order;
use App\Models\Menu;
use App\Models\Mini\MiniPath;
use App\Models\OldWithNew\PartyA;
use App\Models\OldWithNew\PartyB;
use App\Models\Order\Bill as OrderBill;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeOutOrder;
use App\Models\PersionPayOrder;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use App\Models\StatisticsDay;
use App\Models\StatisticsOrder;
use App\Models\Store\StoreGoodsSku;
use App\Models\TakeoutOrder as ModelsTakeoutOrder;
use App\Services\BillService;
use App\Services\StaticService;
use App\Services\SwooleJobService;
use App\Services\VoiceService;
use GuzzleHttp\TransferStats;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use App\Models\ApplyMessage;
class Hello extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hello';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $order = TakeOutOrder::where('scene', 2)->find(33);

        $type = 'sms';
        $model = ApplyMessage::where("uniacid", $order->uniacid)->where("type", 'sms' . ucwords($type))->where("state", 1)->first();
        if ($model) {
            $model->send($order);
        }
        // $redis = Redis::connection('cache');
        // $configprefix = config('cache.prefix');
        // $keys = $redis->keys($configprefix . ':InstoreCheckout:Store:*');
        // $configprefix2 = config('database.redis.options.prefix');
        // foreach ($keys as $key) {
        //     Redis::del(str_replace($configprefix2, '', $key));
        // }
        // $miniPath = storage_path('app/weixinOpen/ybwm_open');
        // $shopPath = storage_path('app/merchant/ybv3_merchant');
        // if (File::isDirectory($miniPath)) {
        //     File::deleteDirectory($miniPath);
        // }
        // if (File::isDirectory($shopPath)) {
        //     File::deleteDirectory($shopPath);
        // }
        // $data = [
        //     'agent_id' => '202007291001',
        //     'agent_secret' => "11476900311476900311476900311111",
        //     'msg' => "微信收款1000元",
        //     'sbx_id' => 'S01202306251141'
        // ];
        // $res = Http::asJson()->post('http://iot.solomo-info.com:9306/admin/common/msgpush', $data)->getBody()->getContents();
        // var_dump($res);
        // $url = $this->getRedirectUrl('https://v.douyin.com/iL9ykV8J/');
        // $res = parse_url($url);
        // parse_str($res['query'], $params);
        // var_dump($params);
        // exit;
        // $order = PersionPayOrder::find(117);
        // event(new StoreMessageEvent($order,'pay'));
        var_dump(intval(mb_substr(10010, 2, -2, 'utf-8')));
    }
}
