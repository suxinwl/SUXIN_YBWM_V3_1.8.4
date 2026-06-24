<?php

namespace App\Services;

use App\Jobs\SendMessageTask;
use App\Models\BulkOrder;
use App\Models\Member;
use App\Models\MessageConfig;
use App\Models\Post;
use App\Models\ShopWithdrawal;
use App\Models\Staff;
use App\Services\OpenWechat\ChannelOpenWechat;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MessageConfigService extends BaseService
{
    public static $kidList = [
        'pay' => [
            'tid' => 3450,
            'kidList' => [
                "number1" => "订单编号",
                "amount3" => "订单金额",
                "date7" => "支付时间",
                "thing13" => "下单门店"
            ],
            'sceneDesc' =>
            '订单支付成功通知'
        ],
        'receive' => [
            'tid' => 7942,
            'kidList' => [
                "character_string1" => "取餐码",
                "character_string6" => "订单编号",
                "thing2" => "商家名称",
                "amount3" => "订单金额",
                "phone_number10" => "接单时间"
            ],
            'sceneDesc' => '商家接单通知'
        ],
        'takeMeal' => [
            'tid' => 250,
            'kidList' => [
                "number52" => "取餐码",
                "thing23" => "门店名称",
                "thing24" => "门店地址",
                "date14" => "自提时间",
                "thing20" => "备注"
            ],
            'sceneDesc' => '取餐提醒'
        ],
        'delivery' => [
            'tid' => 584,
            'kidList' => [
                "thing1" => "出餐门店",
                "character_string2" => "订单编号",
                "time4" => "送出时间",
                "name11" => "客户名称",
                "thing9" => "收货地址"
            ],
            'sceneDesc' => '订单配送通知'
        ],
        'refund' => [
            'tid' => 8995,
            'kidList' => [
                "character_string6" => "订单编号",
                "amount2" => "退款金额",
                "thing3" => "退款商品",
                "phrase1" => "退款状态",
                "thing8" => "退款原因"
            ],
            'sceneDesc' => '退款申请状态通知'
        ],
        'complete' => [
            'tid' => 677,
            'kidList' => [
                "character_string2" => "订单号",
                "phrase11" => "订单状态",
                "thing6" => "订单商品",
                "amount7" => "订单金额",
                "thing13" => "门店名称"
            ],
            'sceneDesc' => '订单已完成通知'
        ],
        'integralChange' => [
            'tid' => 9826,
            'kidList' => [
                "character_string5" => "当前积分",
                "character_string2" => "变动数值",
                "thing3" => "变动原因",
                "thing4" => "温馨提示"
            ],
            'sceneDesc' => '积分变更通知'
        ],
        'balanceChange' => [
            'tid' => 919,
            'kidList' => [
                "amount1" => "当前余额",
                "amount2" => "变动金额",
                "time10" => "变更时间",
                'thing3' => "变动原因",
                "thing4" => "温馨提示"
            ],
            'sceneDesc' => '会员余额变动通知'
        ],
        'vipChange' => [
            'tid' => 8428,
            'kidList' => [
                'time2' => "变更时间",
                "phrase3" => "当前等级",
                "thing1" => "备注说明"
            ],
            'sceneDesc' => '会员等级变动提醒'
        ],
        'activity' => [
            'tid' => 7372,
            'kidList' => [
                "thing9" => "活动类型",
                "time6" => "活动时间",
                'thing2' => "活动描述",
                'thing8' => "温馨提示"
            ],
            'sceneDesc' => '活动开始提醒'
        ],
        'coupon' => [
            'tid' => 15855,
            'kidList' => [
                "thing1" => "优惠券名称",
                "thing2" => "券类型",
                "number9" => "优惠券数量",
                'time3' => "过期时间",
                'thing4' => "备注"
            ],
            'sceneDesc' => '优惠券到账通知'
        ],
        'couponOverdue' => [
            'tid' => 303,
            'kidList' => [
                "thing10" => "优惠券名称",
                "number7" => "优惠券数量",
                'time4' => "过期时间",
                'thing6' => "温馨提示"
            ],
            'sceneDesc' => '优惠券到期提醒'
        ],
        'queuingUp' => [
            'tid' => 10000018,
            'kidList'=>[
                '排队号'
            ]
        ]
    ];

    public static $wechatkidList = [];

    public static $wechatTempList = [];

    public static function addTemplate($type, $uniacid)
    {
        $app = ChannelOpenWechat::miniProgram($uniacid);
        $model = MessageConfig::where("type", $type)->first();
        if (empty($model)) {
            throw new BadRequestException('模板数据不存在');
        }
        $keywordList = $app->subscribe_message->getTemplateKeywords($model->tid);
        if ($keywordList['errcode'] != 0) {
            throw new BadRequestException(json_encode($keywordList, 320));
        }
        $keywordList = $keywordList['data'];
        foreach ($keywordList as $Key => $keyword) {
            $key = array_search($keyword['name'], explode(',', $model->keyword));
            if ($key) {
                $list[$key] = $keyword['kid'];
            }
        }
        $res = $app->subscribe_message->addTemplate($model->tid, $list, $model->title);
        if ($res['errcode'] != 0) {
            Log::error(json_encode($res, 320));
            throw new BadRequestException(json_encode($res, 320));
        }
        return $res['priTmplId'];
    }

    public static function addWechatTemplate($type, $uniacid)
    {
        $app = ChannelOpenWechat::officialAccount($uniacid);
        $kid = self::$wechatTempList[$type];
        $res = $app->template_message->addTemplate($kid['tid']);
        if ($res['errcode'] != 0) {
            throw new BadRequestException($res['errmsg']);
        }
        return $res['template_id'];
    }
    public static function sendPayOkMessage($orderId)
    {
        $message = [
            'touser' => '',
            "template_id" => '',
            'lang' => "zh_CN",
            "data" => [
                "first" => ['value' => ''],
                'keyword1' =>  ['value' => ''],
                'keyword2' => ['value' => ''],
                'keyword3' => ['value' => ''],
                'keyword4' => ['value' => ''],
                'keyword5' => ['value' => ''],
                'remark' => ['value' => ''],
            ],
        ];
    }
}
