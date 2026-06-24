<?php

namespace App\Models\WindowCoupon;

use App\Models\Coupon\Coupon as CouponCoupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stores extends Model
{
    public $_couponList;
    use HasFactory;
    protected $table = 'window_coupon_stores';
    protected $fillable = ['uniacid', 'windowCouponId', 'storeId', 'type'];


}
