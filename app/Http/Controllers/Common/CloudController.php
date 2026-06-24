<?php
namespace App\Http\Controllers\Common;
use App\Models\Admin\Apply;
use App\Models\ApplyPlugs;
use App\Events\MemberRegisteredEvent;
use App\Http\Controllers\Controller;
use App\Models\BaiduAi;
use App\Models\BulkPackage;
use App\Models\BulkPackageGoods;
use App\Models\BulkPackageGoodsGroup;
use App\Models\Circle;
use App\Models\Douyin;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Kuaishou;
use App\Models\MemberBind;
use App\Models\OpenWechat;
use App\Models\Order\Bill;
use App\Models\PersionPayOrder;
use App\Models\Order\OrderGoods;
use App\Models\PrinterLog;
use App\Models\QueuingUp;
use App\Models\Recipe\Recipe;
use App\Models\Recipe\RecipeGoods;
use App\Models\Recipe\RecipeGoodsSku;
use App\Models\RobotLog;
use App\Models\RobotPush;
use App\Models\Order\TakeOutOrder;
use App\Models\ShortLink;
use App\Models\TiktokVerifyList;
use App\Models\Wechat;
use App\Models\Wechat\Kernel\Exceptions\Exception;
use App\Models\Ztkj;
use App\Services\ConfigService;
use App\Services\Delivery\QulaidaService;
use App\Services\Delivery\ShunfengService;
use App\Services\InStoreOrderService;
use App\Services\MemberAccountService;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\OrderService;
use App\Services\Print\FeieLabelContent;
use App\Services\Print\SpyunContent;
use App\Services\Print\YlyContent;
use App\Services\Pay\WechatPay;use App\Services\ShortLinkService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Config;
use App\Models\Order\OrderIndex;
use App\Models\Sms;
use EasyWeChat\Factory;
use App\Models\Ali;
use App\Services\PlugService;
use App\Tasks\WriteAuthTask;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Robot;
use App\Models\PostOrder;
use App\Models\Post;
use App\Models\Store;
use App\Models\WeCom;
use App\Models\Geohash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SpecsImport;
use App\Models\Hardware;
use App\Services\AmqpService;
use App\Events\ApplyEvent;
use App\Models\Install;
use App\Services\PrinterService;
use App\Models\Plug;
use App\Models\ReallysavesMoney;
use Illuminate\Support\Facades\Crypt;
use App\Models\Printer;
use App\Services\Print\FeieContent;
use App\Services\Print\DaquContent;
use App\Models\InStore\Order\Order;
use App\Models\Tables\Table;
use App\Models\Voice;
use App\Models\Delivery\Channel;
use App\Models\StoreConfig;
use App\Models\SmsAccount;
use App\Models\Delivery\Order as deliveryOrder;use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class CloudController extends Controller
{
    public function writeAuth(Request $request)
    {
        $lock = $request->lock;
        if ($lock) {
            file_put_contents('secret.json', $lock);
            Install::updateOrCreate(['type' => 'secret'], [
                'type' => "secret",
                'data' => $lock
            ]);
            $data = getSysInfo();
            $authData = $data['authData'];
            $arr = array_merge(array_merge($authData['channel'], $authData['plug']), $authData['service']);
            Plug::where('id', '>', 0)->where('appType','!=',"free")->update(['status' => 0]);
            Plug::whereIn('appName', $arr)->orWhere('appType','free')->update(['status' => 1]);
        }
        // $lic_str = $request->lic_str;
        // if ($lic_str) {
        //     $dir = base_path();
        //     $licName = $request->licName ?? 'admin.lic';
        //     $name = 'admin.lic';
        //     $file_name = $dir . '/public/' . $name;
        //     mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
        //     file_put_contents($file_name, json_decode($lic_str));
        //     $md5 = md5_file($file_name);
        //     Install::where(['type' => "md5"])->update(['data' => $md5]);
        // }
        echo json_encode(['code' => 200, 'msg' => '成功']);
        die;
    }

    public function replaceLic(Request $request)
    {
        $lic_str = $request->lic_str;
        $dir = base_path();
        $name = 'admin.lic';
        $dir = $dir . '/pubilc';
        $file_name = $dir . '/' . $name;
        $bool = mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
        $bools = file_put_contents($file_name, base64_decode($lic_str));
        $md5 = md5_file($file_name);
        Install::where(['type' => "md5"])->update(['data' => $md5]);
    }

    public function plugNotify(Request $request)
    {
        $lock = $request->lock;
        $json = Crypt::decryptString($lock);
        $plug = json_decode($json, true);
        Log::info($lock);
        // Log::info($plug);
        if ($plug['add']) {
            $locPlugs = Plug::where('appType','!=',"free")->get();
            $diff = collect($plug['add'])->pluck('name')->diff(collect($locPlugs)->pluck('appName'))->all();
            Plug::whereIn("appName", $diff)->update(["status" => 0]);
            foreach ($plug['add'] as $key => $v) {
                PlugService::plugAdd($v['name'], PlugService::typeFormat($v['plug_type']), [
                    'baseName' => $v['use_name'],
                    'baseLogo' => $v['icon'],
                    'baseDesc' => $v['introduction'],
                    'sort' => $v['sort'],
                ]);
            }
        }
        if ($plug['del']) {
            foreach ($plug['del'] as $key => $v) {
                PlugService::plugDel($v);
            }
        }

    }

    public function aaa(){
        $duplicates = RecipeGoods::withTrashed()
            ->groupBy('spuId','recipeId')->havingRaw('count(*) > 1')->get();

        if($duplicates->toArray()){
            foreach ($duplicates as $duplicate) {
                $duplicate->forceDelete();
            }
        }



        $duplicates = RecipeGoodsSku::withTrashed()
            ->groupBy('spuId','recipeId','specMd5')->havingRaw('count(*) > 1')->get();

        foreach ($duplicates as $duplicate) {
            RecipeGoodsSku::where('id',$duplicate->id)->forceDelete(); // 删除除了第一条记录之外的所有记录

        }

        die;


        $duplicates = Store\StoreGoods::withTrashed()
            ->groupBy('storeId','spuId','recipeId')->havingRaw('count(*) > 1')->get();
        if($duplicates->toArray()){
            foreach ($duplicates as $duplicate) {
                $duplicate->forceDelete();
            }
        }
        $duplicates = Store\StoreGoodsSku::withTrashed()
            ->groupBy('storeId','spuId','recipeId','specMd5')->havingRaw('count(*) > 1')->get();
        if($duplicates->toArray()){
            foreach ($duplicates as $duplicate) {
                $duplicate->forceDelete();
            }
        }

        die;





        $order=[
            'uniacid'=>72,
            'storeId'=>114,
            'outTradeNo'=>getTakeOutNo(),
            'takeNo'=>'A01',
            'createdAt'=>time(),
            'userNote'=>'联调测试',
            'receivedName'=>'顺丰同城',
            'receivedTel'=>'13203559287',
            'lng'=>'116.352637',
            'lat'=>'40.014844',
            'receivedAddress'=>'北京市海淀区学清嘉创大厦A座15层',
            'money'=>10,
            'num'=>1,
            'product_detail'=>'测试商品'
        ];

        $res =  ShunfengService::sftcAddOrder($order);
        if ($res['error_code'] != 0) {
            throw new BadRequestException($res['error_msg']);
        }
        $sf_order_id = $res['result']['sf_order_id'];




































        $bill=Bill::where('thirdNo','>','')->whereIn('storeId',[13,14])->get();
        $app = WechatPay::Payment(7, 29);
        foreach ($bill as $v){

            if (isset($app->getConfig()['sp_appid'])) {
                $order = [
                    "transaction_id" => $v->thirdNo,
                    'out_order_no' => getTakeOutNo(),
                    'sub_mchid' => $app->getConfig()['sub_mchid'],
                    'description' => "解冻全部剩余资金"
                ];
            } else {
                $order = [
                    "transaction_id" => $v->thirdNo,
                    'out_order_no' => getTakeOutNo(),
                    "appid" => $app->getConfig()['app_id'],
                    'description' => "解冻全部剩余资金"
                ];
            }
            $body = [
                "json" => $order
            ];
            $response = $app->getClient()->postJson("v3/profitsharing/orders/unfreeze", $body);
            var_dump($response);
        }

        $data= [
            'touser' => 'o4LR55ZasgoKplSf6GlpNHdZdxlU',
            "template_id" => 'Qk3ekQYL8jsv3ywUvxhqqQL6RCcD7b8trnEexeNpBqU',
            'lang' => "zh_CN",
            "page" => "pages/index/my-index",
            "data" => [
                "time3" => '新店开业',
                "thing5" => 'XX门店正式开业，购买了店里的卡券的朋友记得带上你的手机到店XX,尽情享用'
            ],
        ];
        $app = ChannelOpenWechat::miniProgram(18);
        $res = $app->subscribe_message->send($data);


        $a=QulaidaService::getCouriers();
        dd($a);die;










        $uniacid=3;
        $payConfig = PayTemplate::where('uniacid', $uniacid)->where('id', 16)->first();
        if (empty($payConfig)) {
            throw new BadRequestHttpException('支付配置错误');
        }

        $config = $payConfig->data;
        $this->config = $config;
        $app = new Suixingfu();
        $data['mno'] = $config['sxf_shop_id'];
        $data["ordNo"] = '20240630161721264050';

        $res = $app->actionApi('https://openapi.tianquetech.com/query/tradeQuery', $data);
        $message = Request()->all();
        //file_put_contents('fubei.log',json_encode($message).PHP_EOL,FILE_APPEND);
        $ext = json_decode($message['extend'], true);
        try {
            $userId = $ext['userId'];
            $message['transaction_id'] = $message['sxfUuid'];
            $message['trade_type'] = PayEnum::sxfPayChannel($message['payType']);
            $message['payer']['openid'] = $message['openid'];
            $message['out_trade_no'] = $message['ordNo'];
            $payTemprate = PayTemplate::find($payTemprateId);
            $message['payChannel'] = $payTemprate->storeId > 0 ? 2 : 1;
            $payLog = PayLog::where("paySn", $ext['takeOutNo'])->first();
            if (empty($payLog)) {
                dispatch(new WxRefundJob($message, $payTemprate->uniacid, $payTemprateId, 'suixingfu'));
                return false;
            }
            $order = OrderIndex::where('orderSn', $payLog->orderSn)->unpaid()->first();
            if (empty($order)) {
                return false;
            }
            if ($order->type == 1) {
                $res = OrderNotifyService::takeout($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 2) {
                $res = OrderNotifyService::storeValue($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 3) {
                $res = OrderNotifyService::personPay($message, $payLog->orderSn, $payTemprateId);
            }
            if ($order->type == 4) {
                $res = OrderNotifyService::inStore($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 5) {
                $res = OrderNotifyService::pointsMail($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 6) {
                $res = OrderNotifyService::couponPack($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 7) {
                $res = OrderNotifyService::tableReserve($message, $payLog->orderSn, $payTemprateId, $userId);
            }
            if ($order->type == 8) {
                $res = OrderNotifyService::equityCard($message, $payLog->orderSn, $payTemprateId, $userId);
            }

            echo json_encode([
                'code' => 'success',
                'msg' => '成功'
            ]);die;


        } catch (\Exception $e) {

            echo $e->getMessage();die;
        }


//        $res = ConfigService::getSystemSet('official_account');
//        $config = [
//            'app_id' => $res->appId,
//            'secret' => $res->appSecret,
//            'response_type' => 'array',
//        ];
//        $app = Factory::officialAccount($config)->menu;
//        $buttons = [
//            [
//                'name' => '活动制作',
//                'type' => 'view',
//                'url' => 'http://juketang.xyz/wechat/store/home'
//            ],
//            [
//                'name' => '案例中心',
//                'type' => 'view',
//                'url' => 'http://juketang.xyz/wechat/store/case'
//            ],
//            [
//                'name' => '服务中心',
//                'sub_button' => [
//                    [
//                        'name' => '拓客商家中心',
//                        'type' => 'view',
//                        'url' => 'https://www.wemakers.net/b/1130/'
//                    ],
//                    [
//                        'name' => '联系我们',
//                        'type' => 'view',
//                        'url' => 'http://juketang.xyz/page/contact'
//                    ],
//                    [
//                        'name' => '帮助教程',
//                        'type' => 'view',
//                        'url' => 'http://juketang.xyz/wechat/store/home'
//                    ],
//                ]
//            ]
//        ];
//        $a=$app->create($buttons);
//        var_dump($a);die;
//
//
//
//
//
//
//
//        $data=Config::getSystemSet('tiktok_open_platforms');
//        var_dump($data);die;
//
//
//        $uniacid = 4;
//        $order=OrderIndex::where('uniacid',4)
//            ->where('thirdNo','>','')
//            ->orderBy('id', 'desc')->first();
//        $member=MemberBind::where('userId',$order->userId)->first();
//        $config = ChannelOpenWechat::getConfig($uniacid, 'mini');
//        $app = ChannelOpenWechat::miniProgram($uniacid);
//        $res = $app->httpPostJson('wxa/sec/order/is_trade_managed', ['appid' => $config->authorizer_appid]);
//        if ($res['errcode'] != 0 || $res['is_trade_managed'] == false) {
//            return false;
//        }
//        $data = [
//            'order_key' => [
//                'order_number_type' => 2,
//                'transaction_id' => $order->thirdNo,
//            ],
//            'logistics_type' => 4,
//            'delivery_mode' => 1,
//            'shipping_list' => [
//                [
//                    'item_desc' => '订单商品已发货,请确认收货',
//                ]
//            ],
//            'upload_time' => date("c", time()),
//            'payer' => [
//                'openid' => $member->openid
//            ]
//        ];
//
//        $res = $app->httpPostJson('wxa/sec/order/upload_shipping_info', $data);
//
//
//        dd($res);die;


    }

    public function bbb(){
        $data=deliveryOrder::where('id',22)->first();
        $specStr='';
        foreach ($data->order->goods as $v){
            $specStr.=$v->name.'['.$v->attrData['spec'].']'.'('.$v->attrData['attr'].')'.'('.$v->attrData['matal'].'),';
        }
        var_dump($specStr);
        die;






        $a=Kuaishou::prepare(0,'240023376570547');
        var_dump($a);die;
        $queueOrder=QueuingUp::where('id',4)->first();

        $printer = Printer::getHardware(52, 1, '', 2);
        foreach ($printer as $v){
            $content=FeieContent::queuingNumber($queueOrder);
            var_dump($content);die;
            $data = Printer::feiPrint($v, $content, 2);
            $respond = json_decode($data, true);
            dd($respond);
        }
        die;

        $data=BaiduAi::getImgV2(1730131573276476098);
        dd($data);die;
        $taskId='18017565';
        $data=BaiduAi::getImg($taskId);

        dd($data);die;
        $printer = Printer::getHardware(52, 1, '', 2);
        foreach ($printer as $v){
            $content=FeieContent::queuingNumber();
            $data = Printer::feiPrint($v, $content, 2);
            $respond = json_decode($data, true);
            dd($respond);
        }

    }

    //快手回调
    public function kwaiEmpower(Request $request){
        //file_put_contents('kwaiEmpower.log', json_encode($request->all()) . PHP_EOL, FILE_APPEND);
        $res= ConfigService::getSystemSet('kuaishou_open_platforms');
        if(empty($res)){
            return true;
        }
        if($request->code){
            $appid=$res->appKey?:'ks697213521555654560';
            $appSecret=$res->appSecret?:'soW0WS02wKeuqc3yErPM-w';
            $code=$request->code?:'85f40fe9a93aa4404335c4d3e9a1773d67575cd1c65b965a727ff8738b85699a15f65252';
            $url='https://lbs-open.kuaishou.com/oauth2/access_token?app_id='.$appid.'&grant_type=code&code='.$code.'&app_secret='.$appSecret;
            $row=file_get_contents($url);
            //file_put_contents('kwaiEmpower.log', $url . PHP_EOL, FILE_APPEND);
            //file_put_contents('kwaiEmpower.log', $row . PHP_EOL, FILE_APPEND);
            $result=json_decode($row,true);
            if($result['result']==1){
                $config=[
                    'ident'=>'kuaishou_open_platforms',
                    'identName'=>'快手本地生活开放平台',
                    'appKey'=>$res->appKey,
                    'appSecret'=>$res->appSecret,
                    'access_token'=>$result['access_token'],
                    'access_token_expires_time'=>time()+170000,
                    'refresh_token'=>$result['refresh_token'],
                    'refresh_token_expires_time'=>time()+15550000,
                ];
                Config::saveSystemSet($config, 'kuaishou_open_platforms', 0, '快手本地生活开放平台');
                Cache::put('ksToken',$result['access_token'],170000);
            }
        }
        return 'success';
    }


}
