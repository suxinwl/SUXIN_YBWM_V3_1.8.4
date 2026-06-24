<?php

namespace App\Models\Handover;

use App\Models\Collect;
use App\Models\InStore\Order\Order as InstoreOrder;
use App\Models\Handover\Order;
use App\Models\Tables\Table;
use App\Traits\StatisticsTrait;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contents extends Model
{
    public $_dataList;
    public $_lists;
    use HasFactory;
    use StatisticsTrait;
    protected $fillable = [
        'uniacid', 'storeId', 'adminId', 'state', 'startTime', 'endTime', 'contents'
    ];

    protected $appends = [
        'warn', 'unBill', 'dataList', 'orderCount', 'money', 'onlineMoney', 'chuzhiMoney', 'cashMoney', 'balanceMoney', 'payTrend', 'summary', 'sellOut', 'discountTrend'
    ];

    public function getListsAttribute()
    {
        if (!$this->_lists) {
            $endTime = $this->endTime;
            $this->_lists =  Order::where('uniacid', $this->uniacid)
                ->select([
                    'day',
                    'costomPayId',
                    'payType',
                    DB::raw("IFNULL(sum(if(type != 2,orderCount,0)),0) as orderCount"),
                    DB::raw("IFNULL(sum(if(type != 2,money,0)),0) as money"),
                    DB::raw("IFNULL(sum(if(type != 2,sellMoney,0)),0) as sellMoney"),
                    DB::raw("IFNULL(sum(if(type != 2,discountMoney,0)),0) as discountMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),money,0)),0) as onlineMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),orderCount,0)),0) as onlineOrder"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as balanceMoney"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,orderCount,0)),0) as balanceOrder"),
                    DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as weixinMoney"),
                    DB::raw("IFNULL(sum(if(payType between 11 and 19 ,orderCount,0)),0) as weixinOrder"),
                    DB::raw("IFNULL(sum(if(payType between 11 and 19 ,money,0)),0) as weixinMoney"),
                    DB::raw("IFNULL(sum(if(payType between 20 and 29 ,orderCount,0)),0) as alipayOrder"),
                    DB::raw("IFNULL(sum(if(payType between 20 and 29 ,money,0)),0) as alipayMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,orderCount,0)),0) as cashOrder"),
                    DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,money,0)),0) as cashMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,orderCount,0)),0) as ziti"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,money,0)),0) as zitiMoney"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,orderCount,0)),0) as waisong"),
                    DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,money,0)),0) as waisongMoney"),
                    DB::raw("IFNULL(sum(if(type =4 ,orderCount,0)),0) as zhuomaOrder"),
                    DB::raw("IFNULL(sum(if(type =4 ,money,0)),0) as zhuomaMoney"),
                    DB::raw("IFNULL(sum(if(type =3 ,orderCount,0)),0) as dmfOrder"),
                    DB::raw("IFNULL(sum(if(type =3 ,money,0)),0) as dmfMoney"),
                    DB::raw("IFNULL(sum(if(type =2 ,orderCount,0)),0) as chuzhiOrder"),
                    DB::raw("IFNULL(sum(if(type =2 ,money,0)),0) as chuzhiMoney"),
                    DB::raw("IFNULL(sum(deliveryMoney),0) as deliveryMoney"),
                    DB::raw("IFNULL(sum(boxMoney),0) as boxMoney"),
                    DB::raw("IFNULL(sum(refundOrder),0) as refundOrder"),
                    DB::raw("IFNULL(sum(refundMoney),0) as refundMoney"),
                    DB::raw("IFNULL(sum(if(state IN (6,10) AND userId > 0,orderCount,0)),0) as payMember"),
                ])
                ->where('created_at', '>=', $this->startTime)
                ->when($this->endTime, function ($q) use ($endTime) {
                    return $q->where('created_at', '<=', $endTime);
                })
                ->where('uniacid',  $this->uniacid)
                ->where('storeId',  $this->storeId)
                ->where('adminId',  $this->adminId);
        }
        return $this->_lists;
    }

    public function getDataListAttribute()
    {
        if (!$this->_dataList) {
            // $this->_dataList = collect($this->lists)->groupBy('day')->map(function ($list, $key) {

            // })->values();
            $this->_dataList = $this->lists->get();
        }
        return $this->_dataList;
    }

    /**
     * 总收款
     */
    public function getMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'money'), 1, 2);
    }
    public function getOrderCountAttribute()
    {
        return $this->sum($this->dataList, 'orderCount');
    }

    /**
     * 在线支付
     */
    public function getOnlineMoneyAttribute()
    {
        return $this->sum($this->dataList, 'onlineMoney');
    }
    /**
     * 快餐支付
     */
    public function getFastMoneyAttribute()
    {
        return $this->sum($this->dataList, 'fastMoney');
    }
    /**
     * 现金
     */
    public function getCashMoneyAttribute()
    {
        return $this->sum($this->dataList, 'cashMoney');
    }

    public function getBalanceMoneyAttribute()
    {
        return $this->sum($this->dataList, 'balanceMoney');
    }

    /**
     * 储值金额
     */
    public function getChuzhiMoneyAttribute()
    {
        return $this->sum($this->dataList, 'chuzhiMoney');
    }


    public function getPayTrendAttribute()
    {
        $lists = collect($this->lists->whereIn('type', [1, 3, 4])
            ->whereIn('state', [6, 10])
            ->groupBy('payType')->get())
            ->toArray();
        if (empty($lists)) {
            return [];
        }
        $data[] = [
            'name' => "营业收入",
            'sellMoney' => $this->sellMoney,
            'money' => $this->money,
            'discountMoney' => $this->discountMoney,
            'orderCount' => $this->orderCount,
            'refundOrder' => $this->refundOrder
        ];
        collect($lists)->groupBy('payTypeChannel')->each(function ($list) use (&$data) {
            $first = collect($list)->first();
            $data[] = [
                'name' => $first['payTypeFormat'],
                'sellMoney' => $this->sum($list, 'sellMoney'),
                'money' => $this->sum($list, 'money'),
                'discountMoney' =>  $this->sum($list, 'discountMoney'),
                'orderCount' => $this->sum($list, 'orderCount'),
                'refundOrder' => $this->sum($list, 'refundOrder'),
            ];
        })->values();
        return $data;
    }
    /**
     * 渠道统计
     */

     public function getSummaryAttribute()
     {
         $lists = collect($this->lists->whereIn('type', [1, 3, 4])->groupBy('score')->get())->toArray();
         if (empty($lists)) {
             return [];
         }
         $data[] = [
             'name' => "汇总",
             'sellMoney' => $this->sellMoney,
             'money' => $this->money,
             'discountMoney' => $this->discountMoney,
             'orderCount' => $this->orderCount,
             'refundOrder' => $this->refundOrder
         ];
         collect($lists)->groupBy('score')->each(function ($list) use (&$data) {
             $first = collect($list)->first();
             $data[] = [
                 'name' => $first['scoreFormat'],
                 'sellMoney' => $this->sum($list, 'sellMoney'),
                 'money' => $this->sum($list, 'money'),
                 'discountMoney' =>  $this->sum($list, 'discountMoney'),
                 'orderCount' => $this->sum($list, 'orderCount'),
                 'refundOrder' => $this->sum($list, 'refundOrder'),
             ];
         })->values();
         return $data;
     }

    /**
     * 营业外收入
     */
    
     public function getSellOutAttribute()
     {
         $lists = collect($this->lists->whereNotIn('type', [1, 3, 4])->groupBy('type')->get())->toArray();
         if (empty($lists)) {
             return [];
         }
         $data[] = [
             'name' => "汇总",
             'sellMoney' => collect($lists)->sum('sellMoney'),
             'money' => collect($lists)->sum('money'),
             'discountMoney' =>  collect($lists)->sum('discountMoney'),
             'orderCount' => collect($lists)->sum('orderCount'),
             'refundOrder' => collect($lists)->sum('refundOrder'),
         ];
         collect($lists)->groupBy('type')->each(function ($list) use (&$data) {
             $first = collect($list)->first();
             $data[] = [
                 "type" => $first->type,
                 'name' => $first['typeFormat'],
                 'sellMoney' => $this->sum($list, 'sellMoney'),
                 'money' => $this->sum($list, 'money'),
                 'discountMoney' =>  $this->sum($list, 'discountMoney'),
                 'orderCount' => $this->sum($list, 'orderCount'),
                 'refundOrder' => $this->sum($list, 'refundOrder'),
             ];
         })->values();
         return $data;
     }
 

    public function getDiscountTrendAttribute()
    {
        $lists = collect($this->lists->whereIn("type", [4])->get())->filter(function ($list) {
            return !empty(collect($list->subOrder->discountsPlus)->toArray());
        })->pluck('subOrder.discountsPlus')->flatten(1);
        if (empty($lists)) {
            return [];
        }
        $data = [];
        collect($lists)->groupBy('type')->each(function ($list) use (&$data) {
            $first = collect($list)->first();
            $data[] = [
                "type" => $first['type'],
                'name' => $first['title'],
                'sellMoney' => bcmul($this->sum($list, 'sellMoney'), 1, 2),
                'money' => bcmul($this->sum($list, 'money'), 1, 2),
                'count' => collect($list)->count(),
            ];
        })->values();
        return $data;
    }

    public function getWarnAttribute()
    {
        return  collect($this->lists->whereIn("type", [1,4])->get())->pluck('subOrder.generalGoods')->filter(function ($list) {
            return !empty(collect($list)->toArray());
        })->flatten(1)->where('state', 8)->map(function ($goodsItem) {
            return [
                "activityId" => 0,
                "activityName" => "退菜",
                "type" => "goodsBack",
                'money' => $goodsItem->sellMoney,
                "title" => "退菜",
                "num" => $goodsItem->num,
                'goodsName' => $goodsItem->name,
            ];
        })->groupBy('type')->map(function ($list) {
            $first = collect($list)->first();
            return [
                "type" => $first['type'],
                'name' => $first['title'],
                'money' => collect($list)->sum('money'),
                'count' => collect($list)->count(),
                'list' => $list
            ];
        })->merge([[
            "type" => 'tableBack',
            'name' => "撤台",
            'count' => DB::table('instore_order')->where('uniacid', $this->uniacid)
                ->where('storeId', $this->storeId)
                ->where('updated_at', ">=", $this->startTime)
                ->where('updated_at', "<=", $this->endTime)
                ->where('adminId', $this->adminId)
                ->where('state', 0)
                ->count()
        ]])->values();
    }

    public function getUnBillAttribute()
    {
        $list = Table::where('uniacid', $this->uniacid)
            ->where('storeId', $this->storeId)
            ->whereIn('state', [2, 4])
            ->get();
        if ($list) {
            return [[
                'name' => "未结算订单",
                'count' => collect($list)->count(),
                'money' => bcmul(collect($list)->where('order.isPay', 0)->sum('order.money'), 1, 2) ?: 0.00
            ]];
        }
        return [];
    }
}
