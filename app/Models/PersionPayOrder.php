<?php

namespace App\Models;

use App\Models\Member\UserPayStore;
use App\Models\Order\Discount;
use App\Models\Order\OrderIndex;
use App\Models\PayGift\PayGift;
use App\Services\ConfigService;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PersionPayOrder extends BaseModel
{
    use HasFactory;
    protected $table = 'persion_pay_order';
    protected $_config;
    protected $fillable = [
        'orderSn',
        'uniacid',
        'storeId',
        'userId',
        'money',
        'state',
        'expiredTime',
        'isRefund',
        'refundMoney',
        'score',
        'remarks',
        'pickNo',
        'pickFix',
        'adminId',
        'payNum',
        'reason',
        'payTime',
        'couponId',
        'payGiftId',
        'integral',
        'exp',
        'discountMoney',
        'sellMoney'
    ];
    protected $appends = [
        "stateFormat", 'sourceFormat', 'payTypeFormat', 'completionTime', 'orderTypeFormat'
    ];
    protected $with = [
        'store', 'orderIndex', 'admin', 'discountsPlus'
    ];

    public function getConfigAttribute($value)
    {
        if (!$this->_config) {
            $this->_config = collect(ConfigService::getChannelConfig('personPayOrderConfig', $this->uniacid))->toArray();
        }
        return $this->_config;
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'adminId')->select(['id', 'nickname', 'mobile']);
    }

    public function discountsPlus()
    {
        return $this->hasMany(Discount::class, 'orderSn', 'orderSn');
    }


    public function getPickFix()
    {
        $fix = $this->config['orderForm']->personPay ?? '';
        return $fix;
    }
    public function getPickNoAttribute($value)
    {
        return $this->pickFix . $this->attributes['pickNo'];
    }

    public function getPickNo()
    {
        $num  = 'personPay';
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

    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function payGift()
    {
        return $this->hasOne(PayGift::class, 'id', 'payGiftId');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }

    public function orderIndex()
    {
        return $this->hasOne(OrderIndex::class, 'orderSn', 'orderSn')->where('type', 3);
    }
    public function getStateFormatAttribute()
    {
        $data = [
            1 => '待支付',
            6 => "已完成",
            8 => "已退款"
        ];
        return $data[$this->state];
    }

    public function getSourceFormatAttribute()
    {
        return   appTypeFormat($this->score);
    }

    public function getPayTypeFormatAttribute()
    {
        return   $this->orderIndex->payTypeFormat;
    }


    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                OrderIndex::create([
                    'orderSn' => $model->orderSn,
                    'type' => 3,
                    'payType' => 0,
                    'userId' => $model->userId,
                    'score' => $model->score,
                    'uniacid' => $model->uniacid,
                    'storeId' => $model->storeId,
                    'orderId' => $model->id
                ]);
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });
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
            $data['payMember'] = DB::raw("payMember -1");
            $data['money'] = DB::raw("payMember -{$this->money}");
            if ($model->count > 1) {
                $data['repurchase'] = DB::raw("repurchase -1");
            } else {
                $data['newPayUser'] = DB::raw("newPayUser -1");
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
     * 确认收货Day6
     */
    public function getCompletionDayAttribute()
    {
        return date("Y-m-d", strtotime($this->created_at));
    }

    public function getCompletionTimeAttribute()
    {
        return date("Y-m-d H:i:s", strtotime($this->updated_at));
    }

    public function getOrderTypeFormatAttribute()
    {
        return '当面付';
    }
    /**
     * 确认收货Day6
     */
    public function getCompletionHAttribute()
    {
        return date("H", strtotime($this->updated_at));
    }
    public function getQrcodeAttribute()
    {
        return Request()->getSchemeAndHttpHost() . "/s/orderDetail/" . $this->uniacid() . '/?orderId=' . $this->orderSn;
    }
}
