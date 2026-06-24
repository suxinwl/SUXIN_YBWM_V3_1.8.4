<?php
namespace App\Models\PayGift;
use App\Models\Member;
use App\Models\Store;
use App\Models\TakeoutOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Coupon\Coupon;
use App\Models\PayGift\PayGift;
class Receive extends Model
{
    use HasFactory;
    public $_couponList;
    protected $table = 'pay_gift_receive';
    protected $fillable = ['orderSn','uniacid', 'payGiftId', 'orderId', 'integral', 'balance', 'couponGive','userId','storeId','couponList'];
    protected $appends = [
        'couponList'
    ];
    protected $casts =  [
        'couponGive'=>'array'
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    public function  payGift()
    {
        return $this->hasOne(PayGift::class, 'id', 'payGiftId');
    }

    public function  member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }
    public function  order()
    {
        return $this->hasOne(TakeoutOrder::class, 'id', 'orderId');
    }
    public function  store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
    public  function getCouponListAttribute()
    {
        if (!$this->_couponList) {
            if ($this->couponGive) {
                $this->_couponList = Coupon::whereIN('id', $this->couponGive)->get();

            }
        }
        return $this->_couponList;
    }
}
