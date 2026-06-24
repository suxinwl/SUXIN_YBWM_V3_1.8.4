<?php

namespace App\Models;

use App\Jobs\SendMiniMessage;
use App\Jobs\SendSmsMessage;
use App\Jobs\SendWechatMessage;
use App\Services\OpenWechat\ChannelOpenWechat;
use App\Services\SmsService;
use App\Services\SwooleJobService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class ApplyMessage extends BaseModel
{
    use HasFactory;
    protected $table = 'wechatmessage';
    protected $guarded = [];

    public function message()
    {
        return $this->hasOne(MessageConfig::class, 'type', 'type');
    }

    /****************************************************小程序订阅消息************************************* */
    public function pay($order)
    {
        if (empty($order->userId) || !$order->user) {
            return [];
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : '支付成功';
        return [
            'touser' => $order->user->getMiniOpenId() ?? '',
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/order/detail?id=" . $order->orderSn,
            "data" => [
                "character_string1" => $order->orderSn,
                "amount2" => $order->money,
                "time15" => $order->payTime,
                "thing11" => $order->store->name,
                "thing7" => $notes
            ]
        ];
    }

    public function receive($order)
    {
        if (empty($order->userId)) {
            return [];
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : '商家已接单';
        $goodsName = $order->goodsFormat;
        $goodsName = mb_strlen($goodsName) > 20 ?  mb_substr($goodsName, 0, 17, 'utf-8') . '...' : $goodsName;
        return [
            'touser' => $order->user->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/order/detail?id=" . $order->orderSn,
            "data" => [
                "character_string1" => $order->orderSn,
                "thing2" => $order->store->name,
                "thing8" => $order->sceneFormat,
                "thing4" => $goodsName,
                "thing7" => $notes
            ],
        ];
    }

    public function takeMeal($order)
    {
        if (empty($order->userId)) {
            return [];
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : '等待取货';
        $goodsName = $order->goodsFormat;
        $goodsName = mb_strlen($goodsName) > 20 ?  mb_substr($goodsName, 0, 17, 'utf-8') . '...' : $goodsName;
        return [
            'touser' => $order->user->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/order/detail?id=" . $order->orderSn,
            "data" => [
                "thing1" => $order->pickNo,
                "thing2" => $goodsName,
                "character_string12" => $order->orderSn,
                "thing3" => $order->store->name,
                "thing5" => $notes
            ],
        ];
    }

    public function delivery($order)
    {
        if (empty($order->userId)) {
            return [];
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : '已发货';
        return [
            'touser' => $order->user->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/order/detail?id=" . $order->orderSn,
            "data" => [
                "thing2" => $order->store->name,
                "character_string1" => $order->orderSn,
                "thing14" => $order->deliveryOrder->rider['name'],
                "thing16" => $order->deliveryOrder->rider['mobile'],
                "thing11" => $notes
            ],
        ];
    }

    public function refund($order)
    {
        if (empty($order->userId)) {
            return [];
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : '订单退款通知';
        $goodsName = $order->goodsFormat;
        $goodsName = mb_strlen($goodsName) > 20 ?  mb_substr($goodsName, 0, 17, 'utf-8') . '...' : $goodsName;
        return [
            'touser' => $order->user->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/order/detail?id=" . $order->orderSn,
            "data" => [
                "amount2" => $order->money,
                "phrase4" => $order->state == 8 ? '成功退款' : '退款拒绝',
                "thing5" => $goodsName,
                "character_string6" => $order->orderSn
            ],
        ];
    }


    public function complete($order)
    {
        if (empty($order->userId)) {
            return [];
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : $this->message->title;
        $goodsName = $order->goodsFormat;
        $goodsName = mb_strlen($goodsName) > 20 ?  mb_substr($goodsName, 0, 17, 'utf-8') . '...' : $goodsName;
        return [
            'touser' => $order->user->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/order/detail?id=" . $order->orderSn,
            "data" => [
                "thing1" => $order->stateFormat,
                "amount14" => $order->money,
                "date9" => $order->completionTime,
                "thing13" => $order->store->name,
                "thing12" => $notes
            ],
        ];
    }

    public function integralChange($model)
    {
        if (empty($model) || empty($model->userId) || empty($model->member) || !($model instanceof MemberAccountLog)) {
            return false;
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : $this->message->title;
        return [
            'touser' => $model->member->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/index/my-index",
            "data" => [
                "number3" => $model->account->integral,
                "thing4" => $notes,
                "character_string6" => ($model->type == 1 ? "+" : "-") . $model->value,
                "time7" => $model->created_at->format("Y-m-d H:i:s"),
                "thing8" => $model->notes
            ],
        ];
    }

    public function balanceChange($model)
    {
        if (empty($model) || empty($model->userId) ||  empty($model->member)  || !($model instanceof MemberAccountLog)) {
            return false;
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : $this->message->title;
        return [
            'touser' => $model->member->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/index/my-index",
            "data" => [
                "amount4" => $model->account->balance,
                "amount5" => ($model->type == 1 ? "+" : "-") . $model->value,
                "time6" => $model->created_at->format("Y-m-d H:i:s"),
                "thing7" => mb_substr($model->behaviorFormat, 0, 20),
                "name1" => $notes
            ],
        ];
    }

    public function vipChange($model)
    {
        if (empty($model) || empty($model->userId) || !($model instanceof Member)) {
            return false;
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : $this->message->title;
        return [
            'touser' => $model->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/index/my-index",
            "data" => [
                "thing1" => $model->nickname,
                "thing2" => $model->vip->name,
                "time3" => $model->updated_at->format("Y-m-d H:i:s"),
                "time4" => $model->updated_at->format("Y-m-d H:i:s"),
                "thing12" => $notes
            ],
        ];
    }

    public function couponOverdue($model)
    {
        if (empty($model)) {
            return false;
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : $this->message->title;
        return [
            'touser' => $model->member->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/index/my-index",
            "data" => [
                "thing1" => $model->coupon->name,
                "time2" => $model->endTime,
                "number6" => $model->num,
                "thing5" => $notes,
            ],
        ];
    }

    public function coupon($model)
    {
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : $this->message->title;
        return [
            'touser' => $model['toUser'],
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/index/my-index",
            "data" => [
                "thing1" => $model['name'],
                "phrase2" => $model['type'],
                "thing5" => $notes,
                "number6" => $model['num'],
            ],
        ];
    }

    public function activity($model)
    {
        if (empty($model) || !($model instanceof Member)) {
            return false;
        }
        $notes = !empty($this->notes) ? mb_substr($this->notes, 0, 32) : $this->message->title;
        return [
            'touser' => $model->member->getMiniOpenId(),
            "template_id" => $this->tempId,
            'lang' => "zh_CN",
            "page" => "pages/index/my-index",
            "data" => [
                "thing9" => $model->nickname,
                "thing5" => $model->vip->name,
                "time6" => $model->updated_at->format("Y-m-d H:i:s"),
                "thing2" => $model->updated_at->format("Y-m-d H:i:s"),
                "thing8" => $notes
            ],
        ];
    }




    /****************************************************短信************************************* */
    public function smsPay($order)
    {
        $mobile = $order->user->mobile;
        $msg = ['storeName' => $order->store->name];
        return compact('mobile', 'msg');
    }
    public function smsReceive($order)
    {
        $mobile = $order->user->mobile;
        $msg = ['storeName' => $order->store->name];
        return compact('mobile', 'msg');
    }

    public function smsTakeMeal($order)
    {
        $mobile = $order->user->mobile;
        $msg = ['storeName' => $order->store->name, 'packNo' => $order->packNo];
        return compact('mobile', 'msg');
    }

    public function smsDelivery($order)
    {
        $mobile = $order->user->mobile;
        $msg = ['storeName' => $order->store->name];
        return compact('mobile', 'msg');
    }

    public function smsRefundApply($order)
    {
        $mobile = $order->user->mobile;
        $goodsName = $order->goodsFormat;
        $goodsName = mb_strlen($goodsName) > 20 ?  mb_substr($goodsName, 0, 17, 'utf-8') . '...' : $goodsName;
        $msg = ['goodsName' => $goodsName, 'state' => $order->stateFormat];
        return compact('mobile', 'msg');
    }

    public function smsIntegralChange($account)
    {
        $value =  ($account->type == 1 ? '+' : '-') . $account->value;
        $mobile = $$account->member->mobile;
        $msg = ['money' => $value, 'balance' => $account->atLast];
        return compact('mobile', 'msg');
    }

    public function smsBalanceChange($account)
    {
        $mobile = $account->member->mobile;
        $msg = ['money' => $account->value, 'balance' => $account->atLast];
        return compact('mobile', 'msg');
    }

    public function smsVipChange($member)
    {
        $mobile = $member->mobile;
        $msg = ['vipLevel' => $member->vip->name];
        return compact('mobile', 'msg');
    }

    public function smsNewOrder($order)
    {
//        $admins =  Admin::where('subMessage', 1)
//            ->where('uniacid', $order->uniacid)
//            ->where('status', 1)
//            ->whereHas('stores', function ($q) use ($order) {
//                return $q->where('admin_storeIds.store_id', $order->storeId);
//            })
//            ->get();
        $store=Store::where('id',$order->storeId)->first()->toArray();
        $mobile = [$store['mobile']];
        $msg = ['storeName' => $order->store->name];
        return compact('mobile', 'msg');
    }


    public function smsRefundOrder($order)
    {
        $admins =  Admin::where('subMessage', 1)
            ->where('uniacid', $order->uniacid)
            ->where('status', 1)
            ->whereHas('stores', function ($q) use ($order) {
                return $q->where('admin_storeids.store_id', $order->storeId);
            })
            ->get();
        $mobile = collect($admins)->pack('mobile')->all();
        $msg = ['storeName' => $order->store->name];
        return compact('mobile', 'msg');
    }

    public function msg()
    {
        return $this->hasOne(MessageConfig::class, 'type', 'type');
    }

    public function send($order)
    {

        try {
            if ($this->msg->sendType == 'sms') {
                return $this->smsSend($order);
            }
            if ($this->msg->sendType == 'mini') {
                return $this->miniSend($order);
            }
            if ($this->msg->sendType == 'wechat') {
                return $this->smsSend($order);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function miniSend($order)
    {
        try {
            $data = $this->{$this->type}($order);
            if (empty($data)) {
                return true;
            }
            $app = ChannelOpenWechat::miniProgram($this->uniacid);
            $res = $app->subscribe_message->send($data);
            Log::error($res);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
    public function smsSend($order)
    {
        try {
            $data = $this->{$this->type}($order);
            //file_put_contents('sms.log',json_encode($data).PHP_EOL,FILE_APPEND);
            $temp = lcfirst(str_replace('sms', '', $this->type));
            if (empty($data['mobile'])) {
                return true;
            }
            $smsModel = new SmsService();
            $smsModel->sendSms($data['mobile'], $temp, $data['msg'], $this->uniacid);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
    public function wechatSend($order)
    {
        try {
            $data = $this->{$this->type}($order);
            if (empty($data)) {
                return true;
            }
            $app = ChannelOpenWechat::officialAccount($order->uniacid);
            $res = $app->template_message->send($data);
            Log::error($res);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}
