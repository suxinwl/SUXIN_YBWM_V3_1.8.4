<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TakeoutOrder;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class OrderIndex extends BaseModel
{
    protected $table = 'order_index';
    protected $guarded = [];
    use HasFactory;
    //今日营业额
    public function turnover($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = TakeoutOrder::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->where('state', 0)->where('refundMoney', 0)->sum('money');
    }

    //今日支付金额
    public function paymentAmount($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = TakeoutOrder::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->where('state', 0)->sum('money');
    }



    //待支付订单
    public function orderPaidNum($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = OrderIndex::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->where('state', 0)->count();
    }
    //待接单订单
    public function orderPendingNum($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = TakeoutOrder::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->where('state', 2)->count();
    }
    //待自提订单
    public function orderCollectedNum($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = TakeoutOrder::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->where('diningType', 1)->where('state', 2)->count();
    }
    //待审批售后
    public function orderAfterSalesNum($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = TakeoutOrder::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->where('state', 7)->count();
    }
    //今日新增用户
    public function newUserNum($uniacid, $startTime = null, $endTime = null)
    {
        $query = Member::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        return $query->count();
    }
    //30天内新增用戶chart图
    public function newUserChar($uniacid, $startTime = null, $endTime = null)
    {
        $query = Member::select(DB::raw("DATE_FORMAT(created_at,'%y-%m-%d') as day),count(distinct id)"))
            ->where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        return $query->groupBy('date')->orderBy('date', 'desc')->get();
    }
    //今日消费用户
    public function consumerUserNum($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = OrderIndex::where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->where('state', 2)->groupBy('userId')->count();
    }

    //30天内消费用户chart图
    public function consumerUserChar($uniacid, $storeId, $startTime = null, $endTime = null)
    {
        $query = OrderIndex::select(DB::raw("DATE_FORMAT(created_at,'%y-%m-%d') as day),count(distinct userId)"))
            ->where('uniacid', $uniacid);
        if ($startTime && $endTime) {
            $query = $query->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        }
        if ($storeId) {
            $query = $query->where('storeId', $storeId);
        }
        return $query->groupBy('userId')->get();
    }

    //堂食订单金额
    //配送订单金额
    //自取订单金额
    //快递订单金额
    //支付占比
    //营业实收明细
}
