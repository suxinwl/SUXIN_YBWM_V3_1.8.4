<?php

namespace App\Models\Statistics;

use App\Models\Admin\Apply;
use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Order\TakeOutOrder;
use App\Models\StatisticsDay;
use App\Models\StatisticsOrder;
use App\Traits\StatisticsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IndexData extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    protected $fillable  = [
        'uniacid', 'storeId'
    ];
    protected $attributes = [
        'storeId' => 0,
    ];
    protected  $appends = [
        'applyExpire', 'toDay', 'userTrend', 'pvUvTrend', 'payMoneyTrend', 'orderCountTrend', 'takeoutTodoList', 'selfTodoList', 'sellMoneyTop', 'payTop'
    ];

    public function getApplyExpireAttribute()
    {
        $day = date("Y-m-d H:i:s", strtotime("-15day"));
        $model = Apply::select(['id'])->where("timeType", 2)->whereDate("endTime", "<=", $day)->where('id', $this->uniacid)->first();
        return empty($model) ? 0 : 1;
    }

    /**
     * 查询30天的统计数据
     */
    public function getStatisticsDayAttribute()
    {
        $storeId = $this->storeId;
        $timeArr = $this->timeArr();
        if (!$this->_statisticsDay) {
            if (auth('admin')->user()->isAdmin == 0) {
                $query = StatisticsDay::select([
                    'id', 'uniacid', 'day', 'storeId',
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
                    DB::raw('sum(storedValueCount) as storedValueCount'),
                    DB::raw('sum(personPayCount) as personPayCount'),
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
                    DB::raw('sum(inStoreOrder) as inStoreOrder'),
                    DB::raw('sum(inStoreMoney) as inStoreMoney')
                ]);
            } else {
                $query = StatisticsDay::select(['*']);
            }
            $this->_statisticsDay = $query->where('uniacid', $this->uniacid)
                ->when($storeId || $storeId == 0, function ($q) use ($storeId) {
                    if (is_array($storeId)) {
                        $q->whereIn('storeId', $this->storeId);
                    } else {
                        $q->where('storeId', $this->storeId);
                    }
                })
                ->whereBetween('day', [date("Y-m-d", strtotime("-29 day")), date("Y-m-d", time())])
                ->groupBy('day')
                ->get();
        }
        return  $this->_statisticsDay;
    }


    /**
     * 获取今天的数据
     */
    public function getToDayAttribute()
    {
        return collect($this->dataList)->where("day", date("m-d", time()))->first();
    }

    /**
     * 新增会员趋势
     */
    public function getUserTrendAttribute()
    {
        return $this->lineData($this->statisticsDay, ['newMember']);
    }

    /**
     * pv趋势
     */
    public function getPvUvTrendAttribute()
    {
        return $this->lineData($this->statisticsDay, ['pv', 'uv']);
    }

    /**
     * 营业额趋势
     */
    public function getPayMoneyTrendAttribute()
    {
        return $this->lineData($this->dataList, ['money'], true);
    }

    /**
     * 营业额趋势
     */
    public function getOrderCountTrendAttribute()
    {
        return $this->pieData($this->dataList, [
            'ziti' => "自提订单",
            'waisong' => "外送订单",
            'personPayCount' => '买单订单',
            'storedValueCount' => "储值订单",
            'inStoreOrder' => "店内订单"
        ]);
    }

    /**
     * 外卖待办事项
     */
    public function getTakeoutTodoListAttribute()
    {
        $storeId = $this->storeId;
        $model =  TakeOutOrder::count($storeId)
            ->where('uniacid', $this->uniacid)
            ->where('scene', 1)
            ->when($storeId, function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $storeId);
                } else {
                    $q->where('storeId', $storeId);
                }
            })
            ->first();
        return $model->makeHidden(['store', 'goods', 'orderIndex', 'user', 'deliveryOrder'])->setAppends([]);
    }

    /**
     * 店内待办事项
     */
    public function getSelfTodoListAttribute()
    {
        $storeId = $this->storeId;
        $model =  TakeOutOrder::count($storeId)
            ->where('uniacid', $this->uniacid)
            ->where('scene', 2)
            ->when($storeId, function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $storeId);
                } else {
                    $q->where('storeId', $storeId);
                }
            })
            ->first();
        return $model->makeHidden(['store', 'goods', 'orderIndex', 'user', 'deliveryOrder'])->setAppends([]);
    }

    public function getSellMoneyTopAttribute()
    {
        $models = StatisticsOrder::select(['id', 'storeId', DB::raw('sum(money) as money')])->where("money", ">", 0)->where('storeId', ">", 0)->where('uniacid', $this->uniacid)->groupBy("storeId")->whereDate('day', date("Y-m-d", time()))->orderBy("money", "desc")->limit(10)->get();
        return $models;
    }

    public function getPayTopAttribute()
    {
        $models = StatisticsOrder::select(['id', 'storeId', DB::raw('sum(orderCount) as orderCount')])->where("orderCount", ">", 0)->where('storeId', ">", 0)->where('uniacid', $this->uniacid)->groupBy("storeId")->whereDate('day', date("Y-m-d", time()))->orderBy("orderCount", "desc")->limit(10)->get();
        return $models;
    }
}
