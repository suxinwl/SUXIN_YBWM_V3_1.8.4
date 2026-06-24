<?php

namespace App\Models;

use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use EasyWeChat\Factory;
use DateTimeInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Services\OpenWechat\ChannelOpenWechat;
class Wechat extends BaseModel
{
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    public static function config()
    {
        $res = ConfigService::getSystemSet('official_account');
        if (!$res->appId || !$res->appSecret) {
            throw new BadRequestHttpException('公众号配置信息不能为空');
        }
        $config = [
            'app_id' => $res->appId,
            'secret' => $res->appSecret,
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        return $app;
    }

    public static function newOrder($keyword1 = '', $keyword2 = '', $keyword3 = '', $keyword4 = '', $keyword5 = '')
    {
        $keyword1 = $keyword1 ?: '便利小店';
        $keyword1 = mb_substr($keyword1, 0, 20);
        $keyword2 = $keyword2 ?: '20230829132565';
        $keyword2 = mb_substr($keyword2, 0, 20);
        $keyword3 = $keyword3 ?: '红烧鱼块*2,干锅白菜*1';
        $keyword3 = mb_substr($keyword3, 0, 20);
        $keyword4 = $keyword4 ?: '60';
        $keyword4 = mb_substr($keyword4, 0, 20);
        $keyword5 = $keyword5 ?: date('Y-m-d H:i:s', time());
        return [
            'thing12' => [$keyword1, "#173177"],
            'character_string2' => [$keyword2, "#173177"],
            'thing4' => [$keyword3, "#173177"],
            'amount5' => [$keyword4, "#173177"],
            'time6' => [$keyword5, "#173177"],
        ];
    }
    public static function refundApply($keyword1 = '', $keyword2 = '', $keyword3 = '', $keyword4 = '')
    {
        $keyword1 = $keyword1 ?: '便利小店';
        $keyword2 = $keyword2 ?: '20230829132565';
        $keyword3 = mb_substr($keyword3, 0, 20) ?: '60.00';
        $keyword4 = $keyword4 ?: date('Y-m-d H:i:s', time());
        return [
            'thing3' => [$keyword1, "#173177"],
            'character_string8' => [$keyword2, "#173177"],
            'amount5' => [$keyword3, "#173177"],
            'time6' => [$keyword4, "#173177"],
        ];
    }
    public static function deliveryAbnormal($thing4 = '', $time3 = '', $character_string1 = '', $thing6 = '', $amount2 = '')
    {
        $thing4 = $thing4 ?: 'A001';
        $thing4 = mb_substr($thing4, 0, 20);
        $time3 = $time3 ?: date('Y-m-d H:i:s', time());
        $character_string1 = $character_string1 ?: '20230829132565';
        $character_string1 = mb_substr($character_string1, 0, 32);
        $thing6 = $thing6 ?: '配送异常:没有骑手接单';
        $thing6 = mb_substr($thing6, 0, 20);
        $amount2 = $amount2 ?: '60';
        return [
            'thing4' => [$thing4, "#173177"],
            'time3' => [$time3, "#173177"],
            'character_string1' => [$character_string1, "#173177"],
            'thing6' => [$thing6, "#173177"],
            'amount2' => [$amount2, "#173177"],
        ];
    }
    public static function inStoreNewOrder($keyword1 = '', $keyword2 = '', $keyword3 = '', $keyword4 = '', $keyword5 = '')
    {
        $keyword1 = $keyword1 ?: 'A001';
        $keyword1 = mb_substr($keyword1, 0, 20);
        $keyword2 = $keyword2 ?: '60';
        $keyword2 = mb_substr($keyword2, 0, 20);
        $keyword3 = $keyword3 ?: date('Y-m-d H:i:s', time());
        $keyword4 = $keyword4 ?: '20230829132565';
        $keyword4 = mb_substr($keyword4, 0, 20);
        $keyword5 = $keyword5 ?: '堂食';
        $keyword5 = mb_substr($keyword5, 0, 20);
        return [
            'keyword1' => [$keyword1, "#173177"],
            'keyword2' => [$keyword2, "#173177"],
            'keyword3' => [$keyword3, "#173177"],
            'keyword4' => [$keyword4, "#173177"],
            'keyword5' => [$keyword5, "#173177"],
        ];
    }

    public static function createMenu()
    {
        $res = ConfigService::getSystemSet('official_account');
        $config = [
            'app_id' => $res->appId,
            'secret' => $res->appSecret,
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config)->menu;
        $buttons = [
            [
                'name' => '活动制作',
                'type' => 'view',
                'url' => 'http://juketang.xyz/wechat/store/home'
            ],
            [
                'name' => '案例中心',
                'type' => 'view',
                'url' => 'http://juketang.xyz/wechat/store/case'
            ],
            [
                'name' => '服务中心',
                'sub_button' => [
                    [
                        'name' => '拓客商家中心',
                        'type' => 'view',
                        'url' => 'https://www.wemakers.net/b/1130/'
                    ],
                    [
                        'name' => '联系我们',
                        'type' => 'view',
                        'url' => 'http://juketang.xyz/page/contact'
                    ],
                    [
                        'name' => '帮助教程',
                        'type' => 'view',
                        'url' => 'http://juketang.xyz/wechat/store/home'
                    ],
                ]
            ]
        ];
        $a = $app->create($buttons);
        var_dump($a);
        die;
    }

    public static function wxfahuoJob($message){
        file_put_contents('WxfahuoJob.log','我來執行了'. PHP_EOL, FILE_APPEND);
        try {


            $transaction_id = $message['transaction_id'];
            $openid = $message['openid'];
            $uniacid = $message['uniacid'];
            $config = ChannelOpenWechat::getConfig($uniacid, 'mini');
            $app = ChannelOpenWechat::miniProgram($uniacid);
            $res = $app->httpPostJson('wxa/sec/order/is_trade_managed', ['appid' => $config->authorizer_appid]);
            if ($res['errcode'] != 0 || $res['is_trade_managed'] == false) {
                return false;
            }
            $data = [
                'order_key' => [
                    'order_number_type' => 2,
                    'transaction_id' => $transaction_id,
                ],
                'logistics_type' => 4,
                'delivery_mode' => 1,
                'shipping_list' => [
                    [
                        'item_desc' => '订单商品已发货,请确认收货',
                    ]
                ],
                'upload_time' => date("c", time()),
                'payer' => [
                    'openid' => $openid
                ]
            ];
            Log::error($data);
            $res = $app->httpPostJson('wxa/sec/order/upload_shipping_info', $data);
            Log::error($res);
            if ($res['errcode'] != 0) {
                file_put_contents('WxfahuoJob.log',json_encode($res). PHP_EOL, FILE_APPEND);
                return false;
            }
        } catch (\Exception $e) {
            file_put_contents('WxfahuoJob.log',$e->getMessage(). PHP_EOL, FILE_APPEND);
            Log::error($e->getMessage());
        }
    }
}
