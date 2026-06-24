<?php

namespace App\Models;

use App\Models\Member\UserPayStore;
use App\Models\Order\OrderIndex;
use Cache;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StoredValueOrder extends BaseModel
{
    use HasFactory;
    protected $table = 'storevalue_order';
    protected $with = ['store', 'user', 'orderIndex'];
    protected $fillable = [
        'uniacid', "storeId", 'userId', 'score', 'orderSn', 'state', 'data', 'money', 'expiredTime', 'storeValueId'
    ];
    protected $casts =  [
        'data' => 'array',
    ];


    protected $appends =  [
        'stateFormat', 'sourceFormat'
    ];

    public function getExpirationMinuteAttribute()
    {
        if (empty($this->expiredTime)) {
            return 0;
        }
        return round((strtotime($this->expiredTime) - time())  / 60);
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name', 'payChange','isolate']);
    }



    public function storedValue()
    {
        return $this->hasOne(StoredValue::class, 'id', 'storedValueId');
    }


    public function orderIndex()
    {
        return $this->hasOne(OrderIndex::class, 'orderSn', 'orderSn')->where('type', 2);
    }

    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId')->select(['id', 'nickname', 'mobile']);
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
     * 已支付
     */
    public function scopeUnReceived($q)
    {
        return $q->where('state', 2);
    }

    public function setFirst()
    {
        $key = "storedValue:{$this->storeValueId}:{$this->userId}";
        Cache::set($key, 1);
        return true;
    }

    public function getStateFormatAttribute()
    {
        $data = [
            1 => '待支付',
            2 => "已完成"
        ];
        return $data[$this->state];
    }

    public function getSourceFormatAttribute()
    {
        return   appTypeFormat($this->score);
    }


    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                OrderIndex::create([
                    'orderSn' => $model->orderSn,
                    'type' => 2,
                    'payType' => 0,
                    'userId' => $model->userId,
                    'thirdNo' => null,
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
            if ($model->count > 1) {
                $data['repurchase'] = DB::raw("repurchase -1");
            } else {
                $data['newPayUser'] = DB::raw("newPayUser -1");
            }
        } else {
            $data['payMember'] = DB::raw("payMember +1");
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

    public function getQrcodeAttribute()
    {
        return Request()->getSchemeAndHttpHost() . "/s/orderDetail/" . $this->uniacid() . '/?orderId=' . $this->orderSn;
    }

    /**
     * 确认收货Day6
     */
    public function getCompletionDayAttribute()
    {
        return date("Y-m-d", strtotime($this->created_at));
    }

    /**
     * 确认收货Day6
     */
    public function getCompletionHAttribute()
    {
        return date("H", strtotime($this->created_at));
    }

    public function getCompletionTimeAttribute()
    {
        return date("Y-m-d H:i:s", strtotime($this->updated_at));
    }
}
