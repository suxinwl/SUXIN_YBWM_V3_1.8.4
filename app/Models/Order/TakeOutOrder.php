<?php

namespace App\Models\Order;

use App\Console\Commands\Order\CloseExpiredOrder;
use App\Enums\PayEnum;
use App\Jobs\Order\CloseExpiredOrderJob;
use App\Models\BaseModel;
use App\Models\Delivery\Order;
use App\Models\Delivery\Store as DeliveryStore;
use App\Models\Drag;
use App\Models\InStore\Order\ParentOrder;
use App\Models\Member;
use App\Models\Member\UserPayStore;
use App\Models\OrderCollect\OrderCollect;
use App\Models\PayGift\PayGift;
use App\Models\Store;
use App\Services\ConfigService;
use Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TakeOutOrder extends BaseModel
{
    public $_config;
    use HasFactory;
    protected $table = 'takeout_order';

    protected $with = [
        'goods', 'orderIndex', 'store', 'user', 'deliveryOrder', 'payGift', 'takeScreen'
    ];
    protected $hidden = [
        'discount'
    ];
    protected $appends = [
        'discountsPlus', 'payDeliveryMoney', 'goodsSellMoney', 'payStateFormat', 'deliveryDiscountMoney', 'orderTypeFormat', 'discounts', 'deliveryType', 'serverTimeFormat', 'stateFormat', 'diningTypeFormat', 'sourceFormat', 'payTypeFormat', 'goodsMoney', 'refundFormat', 'sceneFormat', 'appointmentFormat', 'goodsFormat'
    ];
    protected $fillable = ['goodsNum', 'orderSn', 'payNum', 'collectId', 'collectNum', 'payGiftId', 'mobile', 'couponId', 'goodsMoney', 'integral', 'exp', 'discountMoney', 'sellMoney', 'uniacid', 'storeId', 'userId', 'contacts', 'address', 'appointment', 'diningType', 'scene', 'boxMoney', 'deliveryMoney', 'money', 'serverTime', 'serverTime', 'notes','expressNumber'];
    protected $casts =  [
        'address' => 'array',
    ];

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name', 'mobile', 'contact', 'storeMobile', 'lat', 'lng', 'address', 'payChange']);
    }
    public function log()
    {
        return $this->hasMany(Log::class, 'orderSn', 'orderSn')->orderBy('id', 'desc');
    }


    public function payGift()
    {
        return $this->hasOne(PayGift::class, 'id', 'payGiftId');
    }

    public function takeScreen()
    {
        return $this->hasOne(TakeScreen::class, 'orderSn', 'orderSn');
    }

    public function orderCollect()
    {
        return $this->hasOne(OrderCollect::class, 'id', 'collectId');
    }

    public function deliveryStoreRule()
    {
        return $this->hasOne(DeliveryStore::class, 'storeId', 'storeId');
    }

    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId')->select(['id', 'nickname', 'avatar', 'isPay', 'mobile']);
    }

    public function getConfigAttribute()
    {
        if (!$this->_config) {
            $this->_config = ConfigService::getChannelConfig('orderSetting', $this->uniacid);
        }
        return $this->_config;
    }

    public function goods()
    {
        return $this->hasMany(OrderGoods::class, 'orderSn', 'orderSn')->withTrashed();
    }

    public function discount()
    {
        return $this->hasMany(Discount::class, 'orderSn', 'orderSn');
    }


    public function getDiscountsAttribute()
    {
        return collect($this->discount)->mapWithKeys(function ($item, $key) {
            $item = $item->toArray();
            return [$item['type'] => $item];
        });
    }

    public function getDiscountsPlusAttribute()
    {
        return $this->discount;
    }

    public function copywriting()
    {
        return $this->hasOne(Drag::class, 'uniacid', 'uniacid')->where('type', 'copywriting')->where('state', 1);
    }

    public function deliveryOrder()
    {
        return $this->hasOne(Order::class, 'orderSn', 'orderSn');
    }

    public function deliveryAbnormal()
    {
        return $this->hasMany(Order::class, 'uniacid', 'uniacid')->where("callState", 2);
    }

    public function getMaterialMoneyAttribute()
    {
        return collect($this->goods)->sum('materialMoney');
    }

    public function orderIndex()
    {
        return $this->hasOne(OrderIndex::class, 'orderSn', 'orderSn');
    }


    public function getExpirationMinuteAttribute()
    {
        if (empty($this->expiredTime)) {
            return 0;
        }
        return round((strtotime($this->expiredTime) - time())  / 60);
    }

    public function getPickNoAttribute($value)
    {
        return $this->pickFix . $value;
    }

    public function getPickNo()
    {
        $num  = intval($this->scene);
        $key = "pick:{$this->uniacid}:{$this->storeId}:{$num}:" . date("Ymd");
        if (Cache::has($key)) {
            $pickNo = intval(Cache::get($key) + 1);
            Cache::set($key, $pickNo);
        } else {
            $pickNo = $this->config['fixation'] ?? 1;
            Cache::set($key, $pickNo);
        }
        return $pickNo;
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->changeBeforState = $model->getOriginal('state') ?? 1;
            $model->expiredTime = null;
            if (!$model->exists) {
                if ($model->state == 1) {
                    $model->expiredTime = $model->config['onAutoOrder'] == 1 && in_array(1, $model->config['autoOrder']) ? date("Y-m-d H:i:s", time() + $model->config['userOverTime'] * 60) : null;
                }
                if ($model->state == 2) {
                    $model->expiredTime = $model->config['onAutoOrder'] == 1 && in_array(2, $model->config['autoOrder']) ? date("Y-m-d H:i:s", time() + $model->config['shopOverTime'] * 60) : null;
                }
            }

            if ($model->getOriginal('state') == 1 && $model->state == 2) {
                $model->expiredTime = $model->config['onAutoOrder'] == 1 && in_array(2, $model->config['autoOrder']) ? date("Y-m-d H:i:s", time() + $model->config['shopOverTime'] * 60) : null;
                if ($model->appointment == 0) {
                    $model->deliveryAppointment = 0;
                    $model->deliveryCollTime = date("Y-m-d H:i:s", $model->deliveryStoreRule->receivingMinutes * 60 + time());
                } elseif ($model->appointment == 1) {
                    if ($model->deliveryStoreRule->advanceOrderType == 2) {
                        $model->deliveryCollTime = date("Y-m-d H:i:s", (strtotime($model->serverTime) - $model->deliveryStoreRule->advanceOrderMinutes * 60));
                    }
                }
            }

            /**
             * 门店接单过后制作中过期时间
             */
            if ($model->getOriginal('state') == 2 && $model->state == 3) {
                if ($model->scene == 1) {
                    if ($model->appointment == 0) {
                        $model->expiredTime = $model->config['onTakeOutOrder'] == 1 && in_array(1, $model->config['chTakeOutType']) ? date("Y-m-d H:i:s", time() + $model->config['takeOutTakingTime'] * 60) : null;
                    } else {
                        $model->expiredTime = $model->config['onTakeOutOrder'] == 1 && in_array(2, $model->config['chTakeOutType']) ? date("Y-m-d H:i:s", strtotime($model->serverTime) - $model->config['takeOutAppointTime'] * 60) : null;
                    }
                } elseif ($model->scene == 2) {
                    if ($model->appointment == 0) {
                        $model->expiredTime = $model->config['onSelfOrder'] == 1 && in_array(1, $model->config['chSelfType']) ? date("Y-m-d H:i:s", time() + $model->config['selfTakingTime'] * 60) : null;
                    } else {
                        $model->expiredTime = $model->config['onSelfOrder'] == 1 && in_array(2, $model->config['chSelfType']) ? date("Y-m-d H:i:s", strtotime($model->serverTime) - $model->config['selfAppointTime'] * 60) : null;
                    }
                }
            }

            if ($model->getOriginal('state') == 3 && $model->state == 4) {
                if ($model->scene == 2) {
                    if ($model->appointment == 0) {
                        $model->expiredTime = $model->config['onSelfOrder'] == 1 && in_array(1, $model->config['chSelfType']) ? date("Y-m-d H:i:s", time() + $model->config['selfAccomplishTime'] * 60) : null;
                    } else {
                        $model->expiredTime = $model->config['onSelfOrder'] == 1 && in_array(2, $model->config['chSelfType']) ? date("Y-m-d H:i:s", strtotime($model->serverTime) - $model->config['selfAppAccomplishTime'] * 60) : null;
                    }
                }
            }

            if ($model->state == 5) {
                if ($model->scene == 1) {
                    if ($model->appointment == 0) {
                        $model->expiredTime = $model->config['onTakeOutOrder'] == 1 && in_array(1, $model->config['chTakeOutType']) ? date("Y-m-d H:i:s", time() + $model->config['takeOutAccomplishTime'] * 60) : null;
                    } else {
                        $model->expiredTime = $model->config['onTakeOutOrder'] == 1 && in_array(2, $model->config['chTakeOutType']) ? date("Y-m-d H:i:s", strtotime($model->serverTime) - $model->config['takeOutAppAccomplishTime'] * 60) : null;
                    }
                }
            }
        });
        static::saved(function ($model) {
            try {
                if (!$model->orderIndex) {
                    OrderIndex::create([
                        'orderSn' => $model->orderSn,
                        'type' => 1,
                        'payType' => 0,
                        'userId' => $model->userId,
                        'thirdNo' => null,
                        'uniacid' => $model->uniacid,
                        'storeId' => $model->storeId,
                        'orderId' => $model->id,
                        'score' => $model->source
                    ]);
                    $key = "payNum:{$model->uniacid}:{$model->storeId}:{$model->userId}";
                    FacadesCache::increment($key, 1);
                }
                ParentOrder::where('orderSn',$model->orderSn)->update(['state' => $model->state]);
                OrderGoods::where('orderSn', $model->orderSn)->update(['state' => $model->state]);
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }


    public function getStateFormatAttribute()
    {
        $data = [
            0 => "已取消",
            1 => "待支付",
            2 => "待接单",
            3 => "制作中",
            4 =>  $this->getState(),
            5 => "配送中",
            6 => "已完成",
            7 => "用户申请退款",
            8 => "已退款"
        ];
        return $data[$this->state];
    }


    public function getDiningTypeFormatAttribute()
    {
        if($this->scene==30){
            return '快递';
        }
        $data = [
            0 => $this->copywriting->data['wmName'] ?? "外送",
            1 => $this->copywriting->data['dbName'] ?? "打包带走",
            2 =>  $this->copywriting->data['zqName'] ?? "店内就餐"
        ];
        return $data[$this->diningType];
    }


    public function getRefundFormatAttribute()
    {
        if ($this->state == 7 ||  $this->state == 8) {
            $data = [
                7 => '待审批',
                8 => "已退款"
            ];
            return $data[$this->state];
        }
        if ($this->refundState == 2) {
            return '退款拒绝';
        }
        return '';
    }

    public function getAppointmentFormatAttribute()
    {
        return $this->appointment == 0 ? "即时单" : "预约单";
    }

    /**
     * 支付方式
     */
    public function getPayStateFormatAttribute()
    {
        if ($this->state == 1) {
            return  "未支付";
        } elseif ($this->state == 8) {
            return  "已退款";
        } elseif ($this->state == 0) {
            return  "未支付";
        } else {
            return "已支付";
        }
    }

    public function getGoodsMoneyAttribute()
    {
        return sprintf("%.2f", collect($this->goods)->sum('money'));
    }

    public function getGoodsSellMoneyAttribute()
    {
        return sprintf("%.2f", collect($this->goods)->sum('sellMoney'));
    }

    public function getPayDeliveryMoneyAttribute()
    {
        $money = $this->deliveryMoney;
        if (isset($this->discounts['deliveryCoupon'])) {
            $money = bcsub($money, $this->discounts['deliveryCoupon']['money'], 2);
        }
        return $money;
    }
    public function getDeliveryDiscountMoneyAttribute()
    {
        if (isset($this->discounts['deliveryCoupon'])) {
            return  $this->discounts['deliveryCoupon']['money'];
        }
        return 0;
    }

    public function getGoodsOriginalMoneyAttribute()
    {
        return sprintf("%.2f", bcadd(collect($this->goods)->sum('sellMoney'), collect($this->goods)->sum('boxMoney')));
    }

    /**
     * 订单来源
     */
    public function getSourceFormatAttribute()
    {
        return   appTypeFormat($this->source);
    }

    /**
     * 支付方式
     */
    public function getPayTypeFormatAttribute()
    {
        if ($this->state == 1) {
            return  "未支付";
        } elseif ($this->state == 0 && $this->changeBeforState == 1) {
            return  "未支付";
        } else {
            return  PayEnum::format($this->orderIndex->payType);
        }
    }



    public function getSceneFormatAttribute()
    {
        $waimai = $this->copywriting->data['wmName'] ?? '外卖';
        $ziqu = $this->copywriting->data['dnName'] ?? '自取';
        return $this->scene == 1 ? $waimai : $ziqu;
    }

    public function getOrderTypeFormatAttribute()
    {
        return $this->scene == 1 ? $this->sceneFormat . "({$this->appointmentFormat})" : $this->sceneFormat . '/' . $this->diningTypeFormat . "({$this->appointmentFormat})";
    }

    public function getServerTimeFormatAttribute()
    {
        return date("m-d H:i", strtotime($this->serverTime));
    }

    public function getState()
    {
        if ($this->diningType == 0) {
            return  "待配送";
        }
        if ($this->diningType == 1) {
            return  "待取单";
        }
        if ($this->diningType == 2) {
            return  "待取单";
        }
    }

    public function scopeCount($q, $storeId = null)
    {
        return $q->select(DB::raw("IFNULL(sum(if(refundState = 2 and deleted_at is null,1,0)),0) as rejectNum,
        IFNULL(sum(if((refundState = 2 and (state = 7 or  state = 8)) and deleted_at is null,1,0)),0) as afterSaleNum,
        IFNULL(sum(if(state = 0 and deleted_at is null,1,0)),0) as closeNum,
        IFNULL(sum(if(state >= 0 and deleted_at is null,1,0)),0) as orderNum,
        IFNULL(sum(if(state=1 and deleted_at is null,1,0)),0) as unpaidCount,
        IFNULL(sum(if(state=2 or (beforRefundState =2 and state=7) and deleted_at is null,1,0)),0) as unReceivedCount,
        IFNULL(sum(if(state = 3 or (beforRefundState =3 and state=7) and deleted_at is null,1,0)),0) as makingCount,
        IFNULL(sum(if(state = 4 or (beforRefundState =4 and state=7) and deleted_at is null,1,0)),0) as waitingCount ,
        IFNULL(sum(if(state=5 or (beforRefundState =5 and state=7) and deleted_at is null,1,0)),0) as deliveryCount,
        IFNULL(sum(if(state =6 and deleted_at is null,1,0)),0) as completeCount,
        IFNULL(sum(if(state =7 and deleted_at is null,1,0)),0) as refundApplyCount,
        IFNULL(sum(if(state =8 and deleted_at is null,1,0)),0) as refundCount"))
            ->withCount(['deliveryAbnormal as deliveryAbnormalCount' => function ($q) use ($storeId) {
                return $q->where('callState', 2)
                    ->when($storeId, function ($q) use ($storeId) {
                        if (is_array($storeId)) {
                            $q->whereIn('storeId', $storeId);
                        } else {
                            $q->where('storeId', $storeId);
                        }
                    });
            }])->when($storeId, function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $storeId);
                } else {
                    $q->where('storeId', $storeId);
                }
            });
    }

    /**
     * 已关闭
     */
    public function scopeClose($q)
    {
        return $q->where('state', 0);
    }


    /**
     * 待支付
     */
    public function scopeUnpaid($q)
    {
        return $q->where('state', 1);
    }

    /**
     * 已支付待接单
     */
    public function scopeUnReceived($q)
    {
        return $q->where('state', 2)->orWhere(function ($q) {
            return $q->where('beforRefundState', 2)->where('state', 7);
        });
    }

    /**
     * 已接单制作中
     */
    public function scopeMaking($q)
    {
        return $q->where('state', 3)->orWhere(function ($q) {
            return $q->where('beforRefundState', 3)->where('state', 7);
        });
    }

    /**
     * 制作完成待配送
     */
    public function scopeWaiting($q)
    {
        return $q->where('state', 4)->orWhere(function ($q) {
            return $q->where('beforRefundState', 4)->where('state', 7);
        });
    }


    /**
     * 配送中
     */
    public function scopeDelivery($q)
    {
        return $q->where('state', 5)->orWhere(function ($q) {
            return $q->where('beforRefundState', 5)->where('state', 7);
        });
    }

    /**
     * 已完成
     */
    public function scopeComplete($q)
    {
        return $q->where('state', 6);
    }

    /**
     * 申请退款
     */
    public function scopeRefundApply($q)
    {
        return $q->where('state', 7);
    }

    /**
     * 已退款
     */
    public function scopeRefund($q)
    {
        return $q->where('state', 8);
    }


    /**
     * 已退款
     */
    public function scopeAfterSale($q)
    {
        return $q->whereIn('state', [7, 8])->orWhere('refundState', 2);
    }

    /**
     * 已拒绝
     */
    public function scopeReject($q)
    {
        return  $q->where('refundState', 2);
    }

    /**
     * 配送异常
     */
    public function scopeDeliveryAbnormal($q)
    {
        return  $q->whereHas('deliveryOrder', function ($q) {
            return $q->where('callState', 2);
        });
    }


    /**
     * 确认收货Day6
     */
    public function getCompletionDayAttribute()
    {
        if ($this->state == 6 || $this->changeBeforState == 6) {
            return date("Y-m-d", strtotime($this->completionTime));
        } elseif ($this->state == 8) {
            return date("Y-m-d", strtotime($this->updated_at));
        } else {
            if (empty($this->completionTime)) {
                return date("Y-m-d", time());
            }
        }
    }

    public function getCompletionHAttribute()
    {
        if (empty($this->completionTime)) {
            return date("H", time());
        }
        return date("H", strtotime($this->completionTime));
    }

    public function addUserPayStore()
    {
        $model = UserPayStore::where('userId', $this->userId)->where('uniacid', $this->uniacid)->where('storeId', $this->storeId)->first();
        if (empty($model)) {
            $model = new UserPayStore();
            $model->uniacid = $this->uniacid;
            $model->storeId = $this->storeId;
            $model->userId = $this->userId;
        }
        $model->count = $model->count + 1;
        $model->save();
    }

    public function refundUserPayStore()
    {
        $model = UserPayStore::where('userId', $this->userId)->where('uniacid', $this->uniacid)->where('storeId', $this->storeId)->first();
        if (empty($model)) {
            $model = new UserPayStore();
            $model->uniacid = $this->uniacid;
            $model->storeId = $this->storeId;
            $model->userId = $this->userId;
            $model->count = 1;
        }
        $model->count = $model->count - 1;
        $model->save();
    }

    public function getUserPayStore($refund = false)
    {
        $model = UserPayStore::updateOrcreate([
            'uniacid' => $this->uniacid,
            'storeid' => $this->storeId,
            'userId' => $this->userId,
        ], [
            'uniacid' => $this->uniacid,
            'storeid' => $this->storeId,
            'userId' => $this->userId,
        ]);
        if ($refund) {
            $data['money'] = DB::raw("money -{$this->money}");
            if ($this->changeBeforState == 6) {
                $data['payMember'] = DB::raw("payMember -1");
                if ($model->count > 1) {
                    $data['repurchase'] = DB::raw("repurchase -1");
                } else {
                    $data['newPayUser'] = DB::raw("newPayUser -1");
                }
            }
        } else {
            $data['payMember'] = DB::raw("payMember +1");
            $data['money'] = DB::raw("money +{$this->money}");
            if ($model->count == 1) {
                $data['newPayUser'] = DB::raw("newPayUser +1");
            } else {
                $data['repurchase'] = DB::raw("repurchase +1");
            }
        }
        return $data;
    }

    public function getStatisticsDataAttribute()
    {
        $data = [];
        if ($this->state == 8) {
            $data = collect($this->getUserPayStore('refund'))->toArray();
        }
        if ($this->state == 6) {
            $data = collect($this->getUserPayStore())->toArray();
        }
        return $data;
    }

    public function getDeliveryTypeAttribute()
    {
        return $this->deliveryStoreRule->deliveryType;
    }

    public function getGoodsFormatAttribute()
    {
        return  collect($this->goods)->implode('nameFormat', ',');
    }



    public function getPickFix()
    {
        $num  = $this->scene . $this->appointment;
        switch ($num) {
            case 20:
                $fix = $this->config['orderForm']['askFor'] ?? '';
                break;
            case 21:
                $fix = $this->config['orderForm']['subscribe'] ?? '';
                break;
            case 10:
                $fix = $this->config['orderForm']['besides'] ?? '';
                break;
            case 11:
                $fix = $this->config['orderForm']['besidesSub'] ?? '';
                break;
            default:
                $fix = '';
                break;
        }
        return $fix;
    }

    public function setLog($log = '')
    {
        return Log::insert([
            'uniacid' => $this->uniacid,
            'orderSn' => $this->orderSn,
            'state' => $this->state,
            'log' => $log,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ]);
    }

    public function getQrcodeAttribute()
    {
        return Request()->getSchemeAndHttpHost() . "/s/orderDetail/" . $this->uniacid . '/?orderId=' . $this->orderSn;
    }
}
