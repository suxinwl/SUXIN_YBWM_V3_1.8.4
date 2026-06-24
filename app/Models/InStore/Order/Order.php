<?php

namespace App\Models\InStore\Order;

use App\Enums\PayEnum;
use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\CostomPay;
use App\Models\Delivery\Store as DeliveryStore;
use App\Models\Drag;
use App\Models\InStore\OrderCheckout;
use App\Models\Member;
use App\Models\Member\MemberBase;
use App\Models\Member\UserPayStore;
use App\Models\Order\Discount;
use App\Models\Order\Log;
use App\Models\Order\OrderGoods;
use App\Models\Order\OrderIndex;
use App\Models\Order\TakeScreen;
use App\Models\Order\User;
use App\Models\OrderCollect\OrderCollect;
use App\Models\PayGift\PayGift;
use App\Models\Store;
use App\Models\Tables\Table;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class Order extends BaseModel
{
    public $_config;
    use HasFactory;
    protected $table = 'instore_order';
    protected $_subGoods;
    protected $appends = [
        'costomPayFormat','backGoods', 'discountMoney', 'goodsMoney', 'goodsSellMoney', 'discountsPlus', 'generalGoods', 'discountsGoods', 'diningTypeFormat', 'stateFormat', 'orderTypeFormat', 'subGoods', 'sourceFormat', 'packagingFormat', 'addFood', 'minutes', 'payTypeFormat', 'goodsFormat'
    ];
    protected $with = [
        'goods', 'table', 'store', 'subOrder', 'user', 'admin', 'takeScreen'
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    protected $fillable = [
        'collectId',
        'packaging',
        'collectNum',
        'payGiftId',
        'mobile',
        'couponId',
        'goodsMoney',
        'integral',
        'exp',
        'discountMoney',
        'sellMoney',
        'uniacid',
        'storeId',
        'userId',
        'contacts',
        'appointment',
        'diningType',
        'scene',
        'boxMoney',
        'money',
        'serverTime',
        'notes',
        'tableId',
        'payType',
        'state',
        'people',
        "addNum",
        'goodsNum',
        'autoReceive',
        'receivePrint',
        'prentOrderSn',
        'source',
        'openTime',
        'adminId',
        'pickFix',
        'pickNo',
        'payNum',
        'tableMoney',
        'tableFormat',
        'tableNum',
    ];

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
        return true;
    }
    public function takeScreen()
    {
        return $this->hasOne(TakeScreen::class, 'orderSn', 'orderSn');
    }


    public function payGift()
    {
        return $this->hasOne(PayGift::class, 'id', 'payGiftId');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'adminId')->select(['id', 'nickname', 'mobile']);
    }

    public function orderCollect()
    {
        return $this->hasOne(OrderCollect::class, 'id', 'collectId');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name', 'mobile', 'contact', 'storeMobile', 'lat', 'lng', 'address', 'payChange']);
    }

    public function subOrder()
    {
        return $this->hasMany(Order::class, 'prentOrderSn', 'orderSn')
            ->with(['orderIndex'])
            ->where('goodsNum', '>', 0)
            ->orderBy('id', 'asc');
    }


    /**
     * 订单来源
     */
    public function getSourceFormatAttribute()
    {
        return   appTypeFormat($this->source);
    }

    /**
     * 订单来源
     */
    public function getAddFoodAttribute()
    {
        $appType = Request()->header('appType', 'mini');
        $appType = appType($appType);
        if ($appType == 11) {
            if ($this->diningType == 4 && in_array($this->state, [3, 4, 5]) && ($this->source == $appType || $this->payType == 2)) {
                return true;
            }
        }
        if (in_array($appType, [1, 2, 3, 12])) {
            if ($this->diningType == 4 && in_array($this->state, [3, 4, 5]) && ($this->source == $appType || ($this->store->inStoreSetting['order']['payMode'] == 1 && $this->payType == 1) || ($this->payType == 2))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 订单来源
     */
    public function getPackagingFormatAttribute()
    {
        $data = [
            0 => '店内就餐',
            1 => '打包带走'
        ];
        return $data[$this->packaging];
    }

    public function getSubGoodsAttribute()
    {
        if (!$this->_subGoods) {
            $this->_subGoods =  collect($this->subOrder)->map(function ($order) {
                return collect($order->goods)->all();
            })->flatten()->values();
        }
        return $this->_subGoods;
    }

    /**
     * 原价商品
     */
    public function getGeneralGoodsAttribute()
    {
        if ($this->goods->isEmpty()) {
            return  collect($this->subGoods)->where('discountType', 0)->values();
        } else {
            return  collect($this->goods)->where('discountType', 0)->values();
        }
    }

    public function getGoodsFormatAttribute()
    {
        if ($this->goods->isEmpty()) {
            return  collect($this->subGoods)->implode('nameFormat', ',');
        }else{
            return  collect($this->goods)->implode('nameFormat', ',');
        }
    }
    /**
     * 优惠商品
     */
    public function getBackGoodsAttribute()
    {
        if ($this->goods->isEmpty()) {
            return  collect($this->subGoods)->where('state', 8)->values();
        } else {
            return  collect($this->goods)->where('state', 8)->values();
        }
    }

    public function getDiscountsGoodsAttribute()
    {
        if ($this->goods->isEmpty()) {
            return  collect($this->subGoods)->where('discountType', ">", 0)->values();
        } else {
            return  collect($this->goods)->where('discountType', ">", 0)->values();
        }
    }

    public function getDiscountsPlusAttribute()
    {
        $goods = $this->goods->isEmpty() ? $this->subGoods : $this->goods;
        $discounts = $this->discounts->isEmpty() ? $this->discountsAll : $this->discounts;
        return collect($goods)->filter(function ($goods) {
            return $goods->discountType > 0;
        })->map(function ($goods, $key) {
            $goods = collect($goods)->toArray();
            $goods = json_decode(json_encode($goods));
            if ($goods->discountType == 1) {
                return [
                    'activityId' => 0,
                    'activityName' => "商品赠送",
                    'type' => 'goodsGive',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => "商品赠送"
                ];
            } elseif ($goods->discountType == 2) {
                return [
                    'activityId' => 0,
                    'activityName' => "商品手动{$goods->discountLabel}",
                    'type' => 'goodsDiscount',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => "手动打折"
                ];
            } elseif ($goods->discountType == 3) {
                return [
                    'activityId' => 0,
                    'activityName' => "商品手动{$goods->discountLabel}",
                    'type' => 'goodsSub',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => "手动减免"
                ];
            } else {
                return [
                    'activityId' => 0,
                    'activityName' => $goods->discountLabel,
                    'type' => 'goodsDiscount',
                    'money' => $goods->discountMoney,
                    'reason' => $goods->reason,
                    'title' => $goods->discountLabel
                ];
            }
        })->merge(collect($discounts)->values());
    }


    public function goods()
    {
        return $this->hasMany(OrderGoods::class, 'orderSn', 'orderSn')->withTrashed();
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'orderSn', 'orderSn');
    }

    public function discountsAll()
    {
        return $this->hasMany(Discount::class, 'prentOrderSn', 'orderSn');
    }

    public function getMinutesAttribute()
    {
        if (in_array($this->state, [6, 7, 8]) && $this->completionTime) {
            return intval((strtotime($this->completionTime) - strtotime($this->openTime)) / 60);
        }
        return null;
    }

    public function orderIndex()
    {
        return $this->hasOne(OrderIndex::class, 'orderSn', 'orderSn');
    }
    public function log()
    {
        return $this->hasMany(Log::class, 'orderSn', 'orderSn')->orderBy('id', 'desc');
    }
    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId')
            ->select(['id', 'nickname', 'avatar', 'isPay', 'mobile']);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'orderSn', 'orderSn');
    }

    public function payFormat()
    {
    }

    public function table()
    {
        return $this->hasOne(Table::class, 'id', 'tableId')->with(['area', 'type']);
    }

    public function  changeData($refresh = false)
    {
        $subOrder = collect($this->subOrder)->whereNotIn('state', [0, 8])->all();
        if ($refresh) {
            $subOrder = collect($subOrder)->map(function ($order) {
                $newOrder = collect(new OrderCheckout(['order' => $order]))->toArray();
                $order->fill($newOrder);
                $order->save();
                return $order;
            });
        }
        $this->tableMoney = collect($subOrder)->sum('tableMoney');
        $this->money = collect($subOrder)->sum('money');
        $this->boxMoney = collect($subOrder)->sum('boxMoney');
        $this->sellMoney = collect($subOrder)->sum('sellMoney');
        $this->materialMoney = collect($subOrder)->sum('materialMoney');
        $this->goodsNum = collect($subOrder)->sum('goodsNum');
        $this->goodsMoney = collect($subOrder)->sum('goodsMoney');
        $this->refundMoney = collect($subOrder)->sum('refundMoney');
        $this->discountMoney = collect($subOrder)->sum('discountMoney');
        $this->payMoney = collect($subOrder)->sum('payMoney');
        $this->integral = collect($subOrder)->sum('integral');
        $this->exp = collect($subOrder)->sum('exp');
        $discountMoney  =  collect($this->discounts)->sum('money');
        $this->money  = bcsub($this->money, $discountMoney, 2);
        $this->discountMoney  = bcadd($this->discountMoney, $discountMoney, 2);
        $this->save();
        return true;
    }

    public function setLog($log = '')
    {
        return Log::insert([
            'uniacid' => $this->uniacid,
            'orderSn' => $this->orderSn,
            'state' => $this->state,
            'log' => $log,
            'created_at' => date("Y-m-d H:i:s", time()),
            'updated_at' => date("Y-m-d H:i:s", time())
        ]);
    }

    public function getDiscountMoneyAttribute()
    {
        if($this->sellMoney>$this->money){
            return bcsub($this->sellMoney, $this->money, 2);
        }
        return '0.00';
    }

    public function getGoodsSellMoneyAttribute()
    {
        return bcmul(collect($this->goods->isEmpty() ? $this->subGoods : $this->goods)->where('state', "!=", 8)->sum('sellMoney'), 1, 2);
    }

    public function getGoodsMoneyAttribute()
    {
        return bcmul(collect($this->goods->isEmpty() ? $this->subGoods : $this->goods)->where('state', "!=", 8)->sum('money'), 1, 2);
    }

    public function getDiningTypeFormatAttribute()
    {
        $data = [
            4 => "桌码点餐",
            5 =>  "牌号送餐",
            6 =>   "叫号取餐"
        ];
        return $data[$this->diningType];
    }

    public function getStateFormatAttribute()
    {
        if ($this->diningType == 4 && !$this->prentOrderSn) {
            $data = [
                0 => "已取消",
                1 => "待支付",
                2 => "待接单",
                3 => "就餐中",
                4 => "待取单",
                5 => "待结账",
                6 => "已完成",
                7 => "已申请退款",
                8 => "已退款"
            ];
        } else {
            $data = [
                0 => "已取消",
                1 => "待支付",
                2 => "待接单",
                3 => ($this->diningType == 4 && $this->prentOrderSn) ? "下单成功" : "制作中",
                4 => "待取单",
                5 => "待结账",
                6 => "已完成",
                7 => "已申请退款",
                8 => "已退款"
            ];
        }
        return $data[$this->state];
    }

    public function getOrderTypeFormatAttribute()
    {
        $data = [
            1 => "桌码点餐",
            2 =>  "送餐到桌",
            3 =>   "叫号取餐"
        ];
        return $data[$this->diningType];
    }

    public function  perentOrder()
    {
        return $this->hasOne(ParentOrder::class, 'orderSn', 'prentOrderSn');
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->changeBeforState = $model->getOriginal('state') ?? 1;
            $model->expiredTime = null;
            if ($model->getOriginal('state') == 2 && $model->state == 3 && $model->diningType != 4) {
                $model->expiredTime = $model->config['onFastOrder'] == 1 ? date("Y-m-d H:i:s", time() + $model->config['fastTakingTime'] * 60) : null;
            }
            if ($model->getOriginal('state') == 3 && $model->state == 4 && $model->diningType != 4) {
                $model->expiredTime = $model->config['onFastOrder'] == 1 ? date("Y-m-d H:i:s", time() + $model->config['fastCompleteTime'] * 60) : null;
            }
        });
        static::saved(function ($model) {
            try {
                if (!$model->orderIndex) {
                    OrderIndex::create([
                        'orderSn' => $model->orderSn,
                        'type' => 4,
                        'payType' => 0,
                        'state' => 1,
                        'isShow' => !empty($model->prentOrderSn) ? 0 : 1,
                        'userId' => $model->userId,
                        'thirdNo' => null,
                        'uniacid' => $model->uniacid,
                        'storeId' => $model->storeId,
                        'orderId' => $model->id,
                        'score' => $model->source,
                        'isSub' => !empty($model->prentOrderSn) ? 1 : 0
                    ]);
                    if ($model->prentOrderSn && $model->userId > 0) {
                        User::updateOrCreate([
                            'uniacid' => $model->uniacid,
                            'userId' => $model->userId,
                            'orderSn' => $model->prentOrderSn
                        ], [
                            'uniacid' => $model->uniacid,
                            'userId' => $model->userId,
                            'orderSn' => $model->prentOrderSn
                        ]);
                    }
                    if ($model->userId > 0) {
                        $key = "payNumInStore:{$model->uniacid}:{$model->storeId}:{$model->userId}";
                        Cache::increment($key, 1);
                    }
                }
                OrderGoods::where('state', '!=', 8)->where('orderSn', $model->orderSn)
                    ->update(['state' => $model->state]);
                OrderIndex::where('orderSn', $model->orderSn)->update(['userId' => $model->userId]);
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
    }

    public function getConfigAttribute()
    {
        if (!$this->_config) {
            $this->_config = collect(ConfigService::getChannelConfig('inStoreOrderConfig', $this->uniacid))->toArray();
        }
        return $this->_config;
    }

    public function getPickFix()
    {
        switch ($this->diningType) {
            case 4:
                return $this->config['orderForm']['order'] ?? '';
                break;
            case 5:
                return $this->config['orderForm']['meals'] ?? '';
                break;
            case 6:
                return $this->config['orderForm']['fastfood'] ?? '';
                break;
            default:
                return '';
        }
    }
    public function getPickNoAttribute($value)
    {
        return $this->pickFix . $value;
    }

    public function getPickNo()
    {
        $num  = intval($this->scene . $this->diningType);
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

    /**
     * 已支付待接单
     */
    public function scopeAdminUnReceived($q)
    {
        return $q->where(function ($q) {
            return $q->where(function ($q) {
                return $q->where('diningType', 4)->whereIn('state', [1, 2])->whereNotNull('prentOrderSn');
            })->orWhere(function ($q) {
                return $q->whereIn('diningType', [5, 6])->where('payType', 1)->where('state', 2);
            });
        })->orWhere('beforRefundState', 2);
    }

    public function refundUserPayStore()
    {
        if ($this->userId > 0) {
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
    }
    /**
     * 支付方式
     */
    public function getPayTypeFormatAttribute()
    {
        if ($this->state == 1) {
            return "未支付";
        } elseif ($this->orderIndex->payType > 100) {
            $pay = CostomPay::find(intval(substr($this->orderIndex->payType, 3)));
            return $pay->name;
        } else {
            return PayEnum::format($this->orderIndex->payType);
        }
    }


    /**
     * 支自定义支付方式
     */
    public function getCostomPayFormatAttribute()
    {
        $res=CostomPay::find($this->orderIndex->costomPayId);
        if($res){
            return  $res['name'];
        }else{
            return  '';
        }

    }
    /**
     * 确认收货Day6
     */
    public function getCompletionDayAttribute()
    {
        if ($this->state == 6) {
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
        return date("H", strtotime($this->completionTime));
    }


    public function getUserPayStore($refund = false)
    {
        if ($this->userId > 0) {
            return ['updated_at' => Carbon::now()->toDateString()];
        }
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
            $data['money'] = DB::raw("payMember -{$this->money}");
            if ($this->changeBeforState) {
                $data['payMember'] = DB::raw("payMember -1");
                if ($model->count > 1) {
                    $data['repurchase'] = DB::raw("repurchase -1");
                } else {
                    $data['newPayUser'] = DB::raw("newPayUser -1");
                }
            }
        } else {
            $data['payMember'] = DB::raw("payMember +1");
            $data['money'] = DB::raw("payMember +{$this->money}");
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

    /**
     * 已支付待接单
     */
    public function scopeUnReceived($q)
    {
        return $q->where(function ($q) {
            return $q->where(function ($q) {
                return $q->where('diningType', 4)
                    ->whereIn('state', [1, 2])
                    ->whereNotNull('prentOrderSn');
            })->orWhere(function ($q) {
                return $q->whereIn('diningType', [5, 6])->where('payType', 1)->where('state', 2)->whereNull('prentOrderSn');
            });
        })->orWhere('beforRefundState', 2);
    }

    public function scopeCount($q)
    {
        return $q->select(DB::raw("IFNULL(sum(if((diningType =4 and state in(1,2) and prentOrderSn is not null ) or (diningType in (5,6) and payType = 1 and state =2 and prentOrderSn is null ) or(beforRefundState =2) and deleted_at is null,1,0)),0) as unReceived,IFNULL(sum(if(state = 3 and  diningType in (5,6) and prentOrderSn is null and deleted_at is null,1,0)),0) as makingNum,IFNULL(sum(if(state = 4 and  diningType in (5,6) and prentOrderSn is null and deleted_at is null,1,0)),0) as waitingNum,IFNULL(sum(if(state = 3 and  diningType=4 and prentOrderSn is null and deleted_at is null,1,0)),0) as diningNum,IFNULL(sum(if(state = 6 and  prentOrderSn is null and deleted_at is null,1,0)),0) as completeNum,IFNULL(sum(if(state = 0 and  prentOrderSn is null and deleted_at is null,1,0)),0) as closeNum,IFNULL(sum(if(state = 8 and  prentOrderSn is null and deleted_at is null,1,0)),0) as refundNum"));
    }

    public function getQrcodeAttribute()
    {
        if ($this->prentOrderSn) {
            return Request()->getSchemeAndHttpHost() . "/s/orderDetail/" . $this->uniacid . '/?orderId=' . $this->prentOrderSn;
        } else {
            return Request()->getSchemeAndHttpHost() . "/s/orderDetail/" . $this->uniacid . '/?orderId=' . $this->orderSn;
        }
    }
}
