<?php

namespace App\Models\Order;

use App\Enums\PayEnum;
use App\Models\BaseModel;
use App\Models\CostomPay;
use App\Models\CouponPack\Order as CouponPackOrder;
use App\Models\EquityCard\Order as EquityCardOrder;
use App\Models\InStore\Order\Order;
use App\Models\Member;
use App\Models\Member\UserPayStore;
use App\Models\PayTemplate;
use App\Models\PersionPayOrder;
use App\Models\PointsMall\Order as PointsMallOrder;
use App\Models\StoredValueOrder;
use App\Services\ConfigService;
use DB;
use App\Models\Store;
use App\Models\TablesReserve\Order as TablesReserveOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class OrderIndex extends BaseModel
{
    use HasFactory;
    public $_subOrder;
    protected $table = 'order_index';
    protected $fillable = ['payTime', 'costomPayId', 'payChannel', 'isTj', 'payType', 'profit_sharing', 'uniacid', 'isSub', 'storeId', 'userId', 'orderSn', 'type', 'score', 'state', 'isShow', 'lucky'];
    protected $attributes = [
        'payType' => 0,
        'isShow' => 1,
        "thirdNo" => '',
        'payTempId' => 0,
    ];
    protected $with = [
        'payTemplate', 'orderPay'
    ];
    protected $appends = [
        'payTypeFormat', 'payChannelFormat', 'payStateFormat', 'mchId', 'scoreFormat'
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    public function scopeUnpaid($q)
    {
        return $q->where('state', 1);
    }

    public function order()
    {
        return $this->hasOne(TakeOutOrder::class, 'orderSn', 'orderSn');
    }
    public function couponPack()
    {
        return $this->hasOne(CouponPackOrder::class, 'orderSn', 'orderSn');
    }
    public function instore()
    {
        return $this->hasOne(Order::class, 'orderSn', 'orderSn');
    }
    public function storeValue()
    {
        return $this->hasOne(StoredValueOrder::class, 'orderSn', 'orderSn');
    }
    public function personPayOrder()
    {
        return $this->hasOne(PersionPayOrder::class, 'orderSn', 'orderSn');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public function orderUser()
    {
        return $this->hasOne(User::class, 'orderSn', 'orderSn');
    }

    public function addUserPayStore()
    {
        $model = UserPayStore::where('userId', $this->userId)
            ->where('uniacid', $this->uniacid)
            ->where('storeId', $this->storeId)
            ->first();
        if (empty($model)) {
            $model = new UserPayStore();
            $model->uniacid = $this->uniacid;
            $model->storeId = $this->storeId;
            $model->userId = $this->userId;
        }
        $model->count = $model->count + 1;
        $model->save();
    }


    /**
     * 订单来源
     */
    public function getScoreFormatAttribute()
    {
        return   appTypeFormat($this->score);
    }

    /**
     * 支付方式
     */
    public function getTypeFormatAttribute()
    {
        $data = [
            1 => '外卖订单',
            2 => '储值订单',
            3 => "收款订单",
            4 => "店内订单"
        ];
        return $data[$this->type];
    }


    /**
     * 支付方式
     */
    public function getPayTypeFormatAttribute()
    {
        if ($this->state == 1) {
            return "未支付";
        } elseif ($this->payType > 100) {
            $pay = CostomPay::find(intval(substr($this->payType, 3)));
            return $pay->name;
        } else {
            return PayEnum::format($this->payType);
        }
    }


    public function orderPay()
    {
        return $this->hasMany(OrderPay::class, 'prentOrderSn', 'orderSn')->orderBy('id', 'asc');
    }

    /**
     * 支付方式
     */
    public function getPayStateFormatAttribute()
    {
        if ($this->type == 4) {
            return $this->subOrder->isPay == 1 ? "已支付" : "未支付";
        }
        if ($this->state == 1) {
            return "未支付";
        } elseif (($this->state > 1 && $this->state < 8) || $this->state == 10) {
            return "已支付";
        } elseif ($this->state == 8) {
            return "已退款";
        } else {
            return "未支付";
        }
    }

    /**
     * 支付方式
     */
    public function getPayChannelFormatAttribute()
    {
        $data = [0 => '-', 1 => '店铺收款', 2 => '门店收款'];
        return $data[$this->payChannel];
    }

    public function payTemplate()
    {
        return $this->hasOne(PayTemplate::class, 'id', 'payTempId');
    }

    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }
    public function getSubOrderAttribute()
    {
        if (empty($this->_subOrder)) {
            switch ($this->type) {
                case 1:
                    $this->_subOrder = TakeOutOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 2:
                    $this->_subOrder = StoredValueOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 3:
                    $this->_subOrder = PersionPayOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 4:
                    $this->_subOrder = Order::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 5:
                    $this->_subOrder = PointsMallOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 6:
                    $this->_subOrder = CouponPackOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 7:
                    $this->_subOrder = TablesReserveOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 8:
                    $this->_subOrder = EquityCardOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
            }
        }
        return $this->_subOrder;
    }

    public function scopePaid($q)
    {
        return $q->where('state', 2)->orWhere('state', 6);
    }

    public function scopeWeixin($q)
    {
        return $q->whereBetween('payType', [1,10,17,18,19,20,21]);
    }
    public function scopeAli($q)
    {
        return $q->whereBetween('payType', [20, 29]);
    }
    public function scopeBalance($q)
    {
        return $q->where('payType', 0)->whereIn('state', [2, 6, 8]);
    }

    public function scopeCash($q)
    {
        return $q->where('payType', 6);
    }

    public function getBalanceAttribute()
    {
        if ($this->user) {
            return $this->user->account->getBalance($this->storeId);
        }
        return "0.00";
    }


    public function getMchIdAttribute()
    {
        if ($this->payTemplate) {
            if ($this->payTemplate->type == 3) {
                return $this->payTemplate->data['fb_shop_id'];
            } elseif ($this->payTemplate->type == 4) {
                return $this->payTemplate->data['sxf_mch_id'];
            } elseif ($this->payTemplate->channel == 'weixin') {
                if ($this->payTemplate->type == 1) {
                    return $this->payTemplate->data['mch_id'];
                } else {
                    return $this->payTemplate->data['sub_mch_id'];
                }
            } elseif ($this->payTemplate->channel == 'alipay') {
                return $this->payTemplate->data['app_id'];
            }
        }
        return null;
    }
}
