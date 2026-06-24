<?php

namespace App\Models\Statistics;

use App\Models\BaseModel;
use App\Models\GoodsCat;
use App\Models\Member;
use App\Models\Member\Vip;
use App\Models\Order\OrderGoods;
use App\Models\Order\TakeOutOrder;
use App\Models\StatisticsDay;
use App\Models\StatisticsOrder;
use App\Traits\StatisticsTrait;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

use function PHPUnit\Framework\isEmpty;

class NewOrderData extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    protected $fillable  = [
        'uniacid', 'storeId', 'timeType'
    ];

    protected  $appends = [
        'discountTrend', 'orderCount', 'storedValueMoney', 'storedValueOrder', 'discountMoney', 'boxMoney', 'money', 'sellMoney', 'deliveryMoney', 'refundMoney', 'refundOrder', 'summary', 'orderTrend', 'goodsCat', 'hourTrend', 'sellOut', 'payTrend'
    ];

    public function scopeCount($q)
    {
    }

    /**
     * 查询统计数据
     */
    public function getQueryAttribute()
    {
        $storeId = $this->storeId;
        $timeArr = $this->timeArr(true);
        return StatisticsOrder::select([
            'day',
            'score',
            'payType',
            'type',
            'scene',
            'h',
            'costomPayId',
            DB::raw("IFNULL(sum(if(type != 2,orderCount,0)),0) as orderCount"),
            DB::raw("IFNULL(sum(if(type != 2,money,0)),0) as money"),
            DB::raw("IFNULL(sum(if(type != 2,sellMoney,0)),0) as sellMoney"),
            DB::raw("IFNULL(sum(if(type != 2,discountMoney,0)),0) as discountMoney"),
            // DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),money,0)),0) as onlineMoney"),
            // DB::raw("IFNULL(sum(if(type != 2 AND payType not in (0,6),orderCount,0)),0) as onlineOrder"),
            // DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as balanceMoney"),
            // DB::raw("IFNULL(sum(if(payType = 0 ,orderCount,0)),0) as balanceMoney"),
            // DB::raw("IFNULL(sum(if(payType = 0 ,money,0)),0) as weixinMoney"),
            // DB::raw("IFNULL(sum(if(payType between 11 and 19 ,orderCount,0)),0) as weixinOrder"),
            // DB::raw("IFNULL(sum(if(payType between 11 and 19 ,money,0)),0) as weixinMoney"),
            // DB::raw("IFNULL(sum(if(payType between 20 and 29 ,orderCount,0)),0) as alipayOrder"),
            // DB::raw("IFNULL(sum(if(payType between 20 and 29 ,money,0)),0) as alipayMoney"),
            // DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,orderCount,0)),0) as cashOrder"),
            // DB::raw("IFNULL(sum(if(type != 2 AND payType = 6 ,money,0)),0) as cashMoney"),
            // DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,orderCount,0)),0) as ziti"),
            // DB::raw("IFNULL(sum(if(type != 2 AND scene = 2 ,money,0)),0) as zitiMoney"),
            // DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,orderCount,0)),0) as waisong"),
            // DB::raw("IFNULL(sum(if(type != 2 AND scene = 1 ,money,0)),0) as waisongMoney"),
            // DB::raw("IFNULL(sum(if(type =4 ,orderCount,0)),0) as zhuomaOrder"),
            // DB::raw("IFNULL(sum(if(type =4 ,money,0)),0) as zhuomaMoney"),
            // DB::raw("IFNULL(sum(if(type =3 ,orderCount,0)),0) as dmfOrder"),
            // DB::raw("IFNULL(sum(if(type =3 ,money,0)),0) as dmfMoney"),
            DB::raw("IFNULL(sum(if(type =2 ,orderCount,0)),0) as chuzhiOrder"),
            DB::raw("IFNULL(sum(if(type =2 ,money,0)),0) as chuzhiMoney"),
            DB::raw("IFNULL(sum(deliveryMoney),0) as deliveryMoney"),
            DB::raw("IFNULL(sum(boxMoney),0) as boxMoney"),
            DB::raw("IFNULL(sum(refundOrder),0) as refundOrder"),
            DB::raw("IFNULL(sum(refundMoney),0) as refundMoney"),
            DB::raw("IFNULL(sum(if(state IN (6,10) AND userId > 0,orderCount,0)),0) as payMember"),
        ])
            ->where('uniacid', $this->uniacid)
            ->when(($storeId), function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $this->storeId);
                } else {
                    $q->where('storeId', $this->storeId);
                }
            })
            ->where('payTime', '>=', $timeArr['startTime'])
            ->where('payTime', '<=', $timeArr['endTime'])
            ->orderBy('day', 'asc')
            ->orderBy('h', 'asc');
    }
    public function getStatisticsDayAttribute()
    {
        if (!$this->_statisticsDay) {
            $this->_statisticsDay = $this->query->groupBy('day')->get();
        }
        return  $this->_statisticsDay;
    }

    public function getStoredValueMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'chuzhiMoney');
    }

    public function getStoredValueOrderAttribute()
    {
        return $this->sum($this->statisticsDay, 'chuzhiOrder');
    }

    /**
     * 营业额
     */
    public function getSellMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'sellMoney');
    }

    /**
     * 营业额
     */
    public function getMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'money');
    }


    /**
     * 优惠金额
     */
    public function getDiscountMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'discountMoney');
    }

    /**
     * 支付订单数(单)
     */
    public function getOrderCountAttribute()
    {
        return $this->sum($this->statisticsDay, 'orderCount');
    }


    /**
     * 客单价(元)
     */
    public function getAveragePriceAttribute()
    {
        return empty($this->money) || empty($this->orderCount) ? '0.00' : bcdiv($this->money, $this->orderCount, 2);
    }



    /**
     * 退款订单数
     */
    public function getRefundOrderAttribute()
    {
        return $this->sum($this->statisticsDay, 'refundOrder');
    }

    /**
     * 退款金额
     */
    public function getRefundMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'refundMoney');
    }
    /**
     * 包装费
     */
    public function getBoxMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'boxMoney');
    }
    /**
     * 配送费
     */
    public function getDeliveryMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'deliveryMoney');
    }

    public function getSummaryAttribute()
    {
        $lists = collect($this->query->whereIn('type', [1, 3, 4])->groupBy('score')->get())->toArray();
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

    public function getOrderTrendAttribute()
    {
        $lists = collect($this->query->whereIn('type', [1, 3, 4])->groupBy('scene')
            ->whereIn('state', [6, 10])
            ->get())->toArray();
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
        collect($lists)->groupBy('scene')->each(function ($list) use (&$data) {
            $first = collect($list)->first();
            if ($first->scene == 0) {
                collect($list)->groupBy('type')->each(function ($list2) use (&$data) {
                    $first = collect($list2)->first();
                    $data[] = [
                        'name' => $first['typeFormat'],
                        'sellMoney' => $this->sum($list2, 'sellMoney'),
                        'money' => $this->sum($list2, 'money'),
                        'discountMoney' => $this->sum($list2, 'discountMoney'),
                        'orderCount' => $this->sum($list2, 'orderCount'),
                        'refundOrder' => $this->sum($list2, 'refundOrder'),
                    ];
                });
            } else {
                $data[] = [
                    'name' => $first['typeFormat'],
                    'sellMoney' => $this->sum($list, 'sellMoney'),
                    'money' => $this->sum($list, 'money'),
                    'discountMoney' =>  $this->sum($list, 'discountMoney'),
                    'orderCount' => $this->sum($list, 'orderCount'),
                    'refundOrder' => $this->sum($list, 'refundOrder'),
                ];
            }
        })->values();
        return $data;
    }

    public function getPayTrendAttribute()
    {
        $lists = collect($this->query->whereIn('type', [1, 3, 4])
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

    public function getGoodsCatAttribute()
    {
        $storeId = $this->storeId;
        $timeArr  = $this->timeArr(true);
        $uniacid = $this->uniacid;
        return GoodsCat::select(['id', 'name'])->where("uniacid", $this->uniacid)
            ->withSum(['orderGoods as sales' => function ($q) use ($storeId, $timeArr) {
                return $q->where(OrderGoods::make()->getTable() . ".completionTime", '>=', $timeArr['startTime'])
                    ->where(OrderGoods::make()->getTable() . ".completionTime", '<=', $timeArr['endTime'])
                    ->withTrashed()
                    ->whereIn('state', [6, 10])
                    ->when(($storeId), function ($q) use ($storeId) {
                        if (is_array($storeId)) {
                            $q->whereIn('storeId', $this->storeId);
                        } else {
                            $q->where('storeId', $this->storeId);
                        }
                    });
            }], 'num')
            ->withSum(['orderGoods as money' => function ($q) use ($storeId,  $timeArr) {
                return $q->where(OrderGoods::make()->getTable() . ".completionTime", '>=', $timeArr['startTime'])
                    ->where(OrderGoods::make()->getTable() . ".completionTime", '<=', $timeArr['endTime'])
                    ->withTrashed()
                    ->whereIn('state', [6, 10])
                    ->when(($storeId), function ($q) use ($storeId) {
                        if (is_array($storeId)) {
                            $q->whereIn('storeId', $this->storeId);
                        } else {
                            $q->where('storeId', $this->storeId);
                        }
                    });
            }], 'money')
            ->having("sales", ">", 0)->orderBy("sales", "desc")
            ->get();
    }
    public function getHourTrendAttribute()
    {
        $lists = collect($this->query->whereIn('type', [1, 3, 4])->groupBy('h')->get())->toArray();
        if (empty($lists)) {
            return [];
        }
        return collect($lists)->groupBy('h')->sortBy('h')->map(function ($list) use (&$data) {
            $first = collect($list)->first();
            return [
                'name' => $first['hFormat'],
                'sellMoney' => $this->sum($list, 'sellMoney'),
                'money' => $this->sum($list, 'money'),
                'discountMoney' =>  $this->sum($list, 'discountMoney'),
                'orderCount' => $this->sum($list, 'orderCount'),
                'refundOrder' => $this->sum($list, 'refundOrder'),
            ];
        })->values();
    }

    public function getSellOutAttribute()
    {
        $lists = collect($this->query->whereNotIn('type', [1, 3, 4])->groupBy('type')->get())->toArray();
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
        $lists = collect($this->query->whereNotIn('type', [1, 3, 4])->where('discountMoney', '>', 0)->groupBy('day')->get())->toArray();
        if (empty($lists)) {
            return [];
        }
        $data[] = [
            'name' => "汇总",
            'sellMoney' => $this->sum($lists, 'sellMoney'),
            'money' => $this->sum($lists, 'money'),
            'discountMoney' =>  $this->sum($lists, 'discountMoney'),
            'orderCount' => $this->sum($lists, 'orderCount'),
            'refundOrder' => $this->sum($lists, 'refundOrder'),
        ];
        collect($lists)->groupBy('type')->each(function ($list) use (&$data) {
            $first = collect($list)->first();
            $data[] = [
                "type" => $first['type'],
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
}
