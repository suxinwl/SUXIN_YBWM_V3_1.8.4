<?php

namespace App\Models\Statistics;

use App\Models\BaseModel;
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

class OrderData extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    protected $fillable  = [
        'uniacid', 'storeId', 'timeType','isolate'
    ];

    protected  $appends = [
        'dataList', 'statisticsDay', 'boxMoney', 'storeMoney', 'deliveryMoney', 'serverMoney', 'refundMoney', 'pvUvTrend', 'goodsTop', 'refundOrder', 'storedValue', 'newMember', 'averagePrice', 'payMember', 'orderCount', 'sellMoney', 'money', 'discountMoney', 'moneyTrend', 'sceneCountTrend', 'sceneMoneyTrend', 'payTrend'
    ];


    /**
     * 查询统计数据
     */
    public function getStatisticsDayAttribute()
    {
        if (!$this->_statisticsDay) {
            $storeId = $this->storeId;
            $timeArr = $this->timeArr();
            $query = StatisticsDay::select([
                'id', 'uniacid', 'day', 'storeId', 'h',
                DB::raw('sum(newMember) as newMember'),
                DB::raw('sum(payMember) as payMember'),
                DB::raw('sum(money) as money'),
                DB::raw('sum(sellMoney) as sellMoney'),
                DB::raw('sum(refundMoney) as refundMoney'),
                DB::raw('sum(orderCount) as orderCount'),
                DB::raw('sum(pv) as pv'),
                DB::raw('sum(uv) as uv'),
                DB::raw('sum(ziti) as ziti'),
                DB::raw('sum(waisong) as waisong'),
                DB::raw('sum(averagePrice) as averagePrice'),
                DB::raw('sum(storedValue) as storedValue'),
                DB::raw('sum(sysStoredValue) as sysStoredValue'),
                DB::raw('sum(miniStoredValue) as miniStoredValue'),
                DB::raw('sum(StoredValueTakeout) as StoredValueTakeout'),
                DB::raw('sum(StoredValueSelf) as StoredValueSelf'),
                DB::raw('sum(balanceOrder) as balanceOrder'),
                DB::raw('sum(balanceMoney) as balanceMoney'),
                DB::raw('sum(miniOrder) as miniOrder'),
                DB::raw('sum(miniMoney) as miniMoney'),
                DB::raw('sum(storedValueCapital) as storedValueCapital'),
                DB::raw('sum(storedValueGive) as storedValueGive'),
                DB::raw('sum(refundOrder) as refundOrder'),
                DB::raw('sum(discountMoney) as discountMoney'),
                DB::raw('sum(balanceWm) as balanceWm'),
                DB::raw('sum(balanceZt) as balanceZt'),
                DB::raw('sum(balanceRefundMoney) as balanceRefundMoney'),
                DB::raw('sum(balanceRefundOrder) as balanceRefundOrder'),
                DB::raw('sum(zitiMoney) as zitiMoney'),
                DB::raw('sum(waisongMoney) as waisongMoney'),
                DB::raw('sum(boxMoney) as boxMoney'),
                DB::raw('sum(deliveryMoney) as deliveryMoney'),
                DB::raw('sum(serverMoney) as serverMoney'),
                DB::raw('sum(storeMoney) as storeMoney'),
                DB::raw('sum(storedValueCount) as storedValueCount'),
                DB::raw('sum(personPayCount) as personPayCount'),
                DB::raw('sum(personPayMoney) as personPayMoney'),
                DB::raw('sum(inStoreOrder) as inStoreOrder'),
                DB::raw('sum(inStoreMoney) as inStoreMoney'),
                DB::raw('sum(cashOrder) as cashOrder'),
                DB::raw('sum(cashMoney) as cashMoney'),
                DB::raw('sum(aliPayOrder) as aliPayOrder'),
                DB::raw('sum(aliPayMoney) as aliPayMoney'),
            ]);
            $this->_statisticsDay = $query->where('uniacid', $this->uniacid)
                ->when($storeId || $storeId == 0, function ($q) use ($storeId) {
                    if (is_array($storeId)) {
                        $q->whereIn('storeId', $this->storeId);
                    } else {
                        $q->where('storeId', $this->storeId);
                    }
                })
                ->where('day', '>=', $timeArr['startTime'])
                ->where('day', '<=', $timeArr['endTime'])
                ->when(in_array(Request()->timeType, [-1, 2]), function ($q) {
                    return $q->groupBy('h')->orderBy('h', 'asc');
                })
                ->when(!in_array(Request()->timeType, [-1, 2]), function ($q) {
                    return $q->groupBy('day');
                })
                ->get();
        }
        return  $this->_statisticsDay;
    }

    /**
     * 营业额
     */
    public function getSellMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'sellMoney'), 1, 2);
    }

    /**
     * 营业额
     */
    public function getMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'money'), 1, 2);
    }


    /**
     * 优惠金额
     */
    public function getDiscountMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'discountMoney'), 1, 2);
    }

    /**
     * 支付订单数(单)
     */
    public function getOrderCountAttribute()
    {
        return intval($this->sum($this->dataList, 'orderCount'));
    }

    /**
     * 支付订单数(人)
     */
    public function getPayMemberAttribute()
    {
        return intval($this->sum($this->dataList, 'payMember'));
    }

    /**
     * 客单价(元)
     */
    public function getAveragePriceAttribute()
    {
        return empty($this->money) || empty($this->orderCount) ? '0.00' : bcdiv($this->money, $this->orderCount, 2);
    }

    /**
     * 会员新增(人)
     */
    public function getNewMemberAttribute()
    {
        return intval($this->sum($this->dataList, 'newMember'));
    }

    /**
     * 会员储值(元)
     */
    public function getStoredValueAttribute()
    {
        return bcmul($this->sum($this->dataList, 'chuzhiMoney'), 1, 2);
    }

    /**
     * 退款订单数
     */
    public function getRefundOrderAttribute()
    {
        return intval($this->sum($this->dataList, 'refundOrder'));
    }

    /**
     * 退款金额
     */
    public function getRefundMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'refundMoney'), 1, 2);
    }

    /**
     * 包装费
     */
    public function getBoxMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'boxMoney'), 1, 2);
    }
    
    /**
     * 配送费
     */
    public function getDeliveryMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'deliveryMoney'), 1, 2);
    }
    /**
     * 服务费
     */
    public function getServerMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'tableMoney'), 1, 2);
    }
    /**
     * 预计收入
     */
    public function getStoreMoneyAttribute()
    {
        return bcmul($this->sum($this->dataList, 'storeMoney'), 1, 2);
    }

    /**
     *订单支付金额趋势
     */
    public function getMoneyTrendAttribute()
    {
        $statisticsDay = $this->dataList;
        return $this->lineData($statisticsDay, ['money'], false, 'day');
    }


    /**
     *订单类别饼图
     */
    public function getSceneCountTrendAttribute()
    {
        $statisticsDay = $this->dataList;
        return $this->pieData($statisticsDay, [
            'ziti' => "自提订单",
            "waisong" => "外卖订单",
            'dmfOrder' => '买单订单',
            'chuzhiOrder' => "储值订单",
            'zhuomaOrder' => "店内订单"
        ], false);
    }


    /**
     *支付金额饼图
     */
    public function getSceneMoneyTrendAttribute()
    {
        $statisticsDay = $this->dataList;
        return $this->pieData($statisticsDay, [
            'zitiMoney' => "自提订单",
            "waisongMoney" => "外卖订单",
            'dmfMoney' => '买单金额',
            'chuzhiMoney' => '储值本金',
            'zhuomaMoney' => '店内金额'
        ], false);
    }

    /**
     *支付占比饼图
     */
    public function getPayTrendAttribute()
    {
        $storeId = $this->storeId;
        $timeArr = $this->timeArr(true);
        $list =  StatisticsOrder::where('uniacid', $this->uniacid)
            ->when(($storeId), function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $this->storeId);
                } else {
                    $q->where('storeId', $this->storeId)->where('type', '!=', 2);
                }
            })
            ->select([
                'costomPayId',
                'payType',
                DB::raw("IFNULL(sum(money),0) as money"),
            ])
            ->where('created_at', '>=', $timeArr['startTime'])
            ->where('created_at', '<=', $timeArr['endTime'])
            ->whereIn('state', [6, 10])
            ->groupBy('payType')
            ->get();
        return   collect($list)->groupBy('payTypeChannel')->map(function ($item) {
            $first = collect($item)->first();
            return [
                'name' =>   $first->payTypeFormat,
                'value' => $this->sum($item, 'money')
            ];
        })->values();
    }

    public function  getGoodsTopAttribute()
    {
        $storeId = $this->storeId;
        $timeArr = $this->timeArr(true);
        $request = Request();
        $user = auth('admin')->user();
        $uniacid = $this->uniacid;
        return OrderGoods::select(['id', 'spuId', 'uniacid', 'storeId', 'name', 'logo'])->addSelect([
            DB::raw("IFNULL(sum(sellMoney),0) as sellMoney"),
            DB::raw("IFNULL(sum(money),0) as money"),
            DB::raw("IFNULL(sum(discountMoney),0) as discountMoney"),
            DB::raw("IFNULL(sum(num),0) as num"),
        ])
            ->where('uniacid', $this->uniacid)
            ->where("completionTime", '>=', $timeArr['startTime'])
            ->where("completionTime", '<=', $timeArr['endTime'])
            ->withTrashed()
            ->whereIn('state', [6, 10])
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("storeId", $storeId);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->groupBy("spuId")
            ->orderBy('num', "desc")
            ->orderBy('sellMoney', "desc")
            ->limit(10)->get();
    }

    /**
     * pv趋势
     */
    public function getPvUvTrendAttribute()
    {
        return $this->lineDataAttr($this->statisticsDay, ['pv', 'uv']);
    }

    // public function getDataListAttribute()
    // {
    //     $storeId = $this->storeId;
    //     $timeArr = $this->timeArr(true);
    //     $lists =  StatisticsOrder::select(['*'])
    //         ->where('uniacid', $this->uniacid)
    //         ->when(($storeId), function ($q) use ($storeId) {
    //             if (is_array($storeId)) {
    //                 $q->whereIn('storeId', $this->storeId);
    //             } else {
    //                 $q->where('storeId', $this->storeId);
    //             }
    //         })
    //         ->where('created_at', '>=', $timeArr['startTime'])
    //         ->where('created_at', '<=', $timeArr['endTime'])
    //         ->orderBy('day', 'desc')
    //         ->get();
    //     if (!$lists) {
    //         return [];
    //     }
    //     return collect($lists)->groupBy('day')->map(function ($list, $key) {
    //         return [
    //             'day' => $key,
    //             'orderCount' => collect($list)->whereNotIn("type", [2])->sum('orderCount'),
    //             'money' => round(collect($list)->whereNotIn("type", [2])->sum('money'), 2),
    //             'sellMoney' => round(collect($list)->whereNotIn("type", [2])->sum('sellMoney'), 2),
    //             'discountMoney' => round(collect($list)->whereNotIn("type", [2])->sum('discountMoney'), 2),
    //             'online' => round(collect($list)->whereNotIn("type", [2])->whereNotIn("payType", [0, 6])->sum('money'), 2) . '/' . collect($list)->whereNotIn("type", [2])->whereNotIn("payType", [0, 6])->sum('orderCount'),
    //             'balance' => round(collect($list)->whereIn("payType", [0])->sum('money'), 2) . '/' . collect($list)->whereIn("payType", [0])->sum('orderCount'),
    //             'weixin' => round(collect($list)->whereBetween("payType", [11, 19])->sum('money'), 2) . '/' . collect($list)->whereNotIn("type", [2])->whereBetween("payType", [11, 19])->sum('orderCount'),
    //             'alipay' => round(collect($list)->whereBetween("payType", [21, 29])->sum('money'), 2) . '/' . collect($list)->whereNotIn("type", [2])->whereBetween("payType", [21, 29])->sum('orderCount'),
    //             'cash' => round(collect($list)->whereNotIn("type", [2])->where("payType", 6)->sum('money'), 2) . '/' . collect($list)->whereNotIn("type", [2])->where("payType", 6)->sum('orderCount'),
    //             'ziqu' => round(collect($list)->whereNotIn("type", [2])->where("scene", 2)->sum('money'), 2) . '/' . collect($list)->whereNotIn("type", [2])->where("scene", 2)->sum('orderCount'),
    //             'waimai' => round(collect($list)->whereNotIn("type", [2])->where("scene", 1)->sum('money'), 2) . '/' . collect($list)->whereNotIn("type", [2])->where("scene", 1)->sum('orderCount'),
    //             'zhuoma' => round(collect($list)->where("type", 4)->sum('money'), 2) . '/' . collect($list)->where("type", 4)->sum('orderCount'),
    //             'dmf' => round(collect($list)->where("type", 3)->sum('money'), 2) . '/' . collect($list)->where("type", 3)->sum('orderCount'),
    //             'chuzhi' => round(collect($list)->where("type", 2)->sum('money'), 2) . '/' . collect($list)->where("type", 2)->sum('orderCount'),
    //             'deliveryMoney' => round(collect($list)->sum('deliveryMoney'), 2),
    //             'boxMoney' => round(collect($list)->sum('boxMoney'), 2),
    //             'refundMoney' => round(collect($list)->sum('refundMoney'), 2) . '/' . collect($list)->sum('refundOrder'),
    //         ];
    //     })->values();
    // }
}
