<?php

namespace App\Models\PointsMall;

use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Order\OrderIndex;
use App\Models\PointsMall;
use App\Models\PointsMallClassification;
use App\Models\Store;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Order extends BaseModel
{
    protected $table = 'points_mall_order';
    protected $with = ['orderIndex', 'store', 'user'];
    protected $fillable = [
        'uniacid',
        'userId',
        'storeId',
        'address',
        'orderSn',
        'points',
        'money',
        'deliveryName',
        'deliverySn',
        'goods',
        'goodsId',
        'scene',
        'diningType',
        'state',
        'payTime',
        'deliveryTime',
        'completionTime',
        'afterSaleTime',
        'afterSaleCompletion',
        'refundMoney',
        'refundState',
        'score',
        'deliveryMoney',
        'changeBeforState',
        'expiredTime',
        'qrCode',
        'storeNotes'
    ];
    use HasFactory;
    protected $casts =  [
        'goods' => 'array',
        'address' => 'array',
    ];
    protected $appends = [
        'stateForamt', 'goodsCategory', 'diningTypeFormat'
    ];

    public function orderIndex()
    {
        return $this->hasOne(OrderIndex::class, 'orderSn', 'orderSn')->where('type', 5);
    }

    public function getStateForamtAttribute()
    {
        $data = [
            0 => "已取消",
            1 => '待支付',
            2 => $this->diningType == 1 ? "待发货" : "待取货",
            3 => "已发货",
            6 => "已完成",
            7 => "退款中",
            8 => "已退款"
        ];
        return $data[$this->state];
    }

    public function store()
    {
        return $this->hasOne(StoreBase::class, 'id', 'storeId')->select(['id', 'address', 'lat', 'lng', 'mobile', 'name', 'contact', 'storeMobile', 'isolate']);
    }
    
    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->changeBeforState = $model->getOriginal('state') ?? 1;
            $model->expiredTime = null;
        });
        static::created(function ($model) {
            try {
                OrderIndex::create([
                    'orderSn' => $model->orderSn,
                    'type' => 5,
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
     * 待配送
     */
    public function scopeUnDelivery($q)
    {
        return $q->where('state', 2)->orWhere(function ($q) {
            return $q->where('changeBeforState', 2)->where('state', 7);
        });
    }

    public function getGoodsCategoryAttribute()
    {
        return PointsMallClassification::where('id', $this->goods['type_id'])->first();
    }

    /**
     * 已配送
     */
    public function scopeDelivery($q)
    {
        return $q->where('state', 3)->orWhere(function ($q) {
            return $q->where('changeBeforState', 3)->where('state', 7);
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
     * 已关闭
     */
    public function scopeRefund($q)
    {
        return $q->where('state', 8);
    }

    /**
     * 申请退款
     */
    public function scopeRefundApply($q)
    {
        return $q->where('state', 7);
    }

    public function getDiningTypeFormatAttribute()
    {
        $data = [
            0 => '系统发放',
            1 => "快递配送",
            2 => "门店自提"
        ];
        return $data[$this->diningType];
    }

    public function user()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function getVerificationCodeAttribute()
    {
        if ($this->qrCode) {
            $img =  QrCode::format('png')->size(200)->generate($this->qrCode);    //format 是指定生成文件格式  默认格式是svg,可以直接在浏览器打开，png不能直接显示
            return 'data:image/png;base64,' . base64_encode($img);
        }
        return null;
    }
}
