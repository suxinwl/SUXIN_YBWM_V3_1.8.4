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
use Illuminate\Support\Facades\Request;

class Balance extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    public $_query;
    protected $fillable  = [
        'uniacid', 'storeId'
    ];
    protected $attributes = [
        'storeId' => 0,
    ];
    protected  $appends = [
        'data', 'balanceOrder', 'balanceMoney', 'balanceRefundMoney', 'balanceRefundOrder', 'storedValueCount', 'storedValueCapital'
    ];
    /**
     * 查询30天的统计数据
     */

    public function getStatisticsDayAttribute()
    {
        if (!$this->_statisticsDay) {
            $this->_statisticsDay =
                $storeId = $this->storeId;
            $timeArr = $this->timeArr(true);
            $lists =  StatisticsOrder::where('uniacid', $this->uniacid)
                ->when(($storeId), function ($q) use ($storeId) {
                    if (is_array($storeId)) {
                        $q->whereIn('storeId', $this->storeId);
                    } else {
                        $q->where('storeId', $this->storeId);
                    }
                })
                ->where('created_at', '>=', $timeArr['startTime'])
                ->where('created_at', '<=', $timeArr['endTime'])
                ->orderBy('day', 'desc')
                ->orderBy('h', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            if (!$lists) {
                return [];
            }
            $this->_statisticsDay = collect($lists)->groupBy('day')->map(function ($list, $key) {
                return [
                    'day' => $key,
                    'balanceOrder' => collect($list)->whereIn("payType", [0])->pluck('orderCount')->sum(),
                    'balanceMoney' => bcmul(collect($list)->whereIn("payType", [0])->pluck('money')->sum(), 1, 2),
                    'balanceRefundMoney' => bcmul(collect($list)->whereIn("payType", [0])->pluck('refundMoney')->sum(), 1, 2),
                    'balanceRefundOrder' => collect($list)->whereIn("payType", [0])->pluck('refundOrder')->sum(),
                    'storedValueCount' => collect($list)->whereIn("type", [2])->pluck('orderCount')->sum(),
                    'storedValueCapital' => bcmul(collect($list)->whereIn("type", [2])->pluck('storedValueMoney')->sum(), 1, 2),
                ];
            })->values();
        }
        return  $this->_statisticsDay;
    }

    public function getDataAttribute()
    {
        return [
            "list" => collect($this->statisticsDay)->forPage(Request()->pageNo ?? 1, Request()->pageSize ?? 10),
            "pageNo" => Request()->pageNum ?? 1,
            "pageSize" => Request()->pageSize ?? 10,
            "total" => collect($this->statisticsDay)->count()
        ];
    }

    public function getBalanceOrderAttribute()
    {
        return $this->sum($this->statisticsDay, 'balanceOrder');
    }
    public function getBalanceMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'balanceMoney');
    }
    public function getBalanceRefundMoneyAttribute()
    {
        return $this->sum($this->statisticsDay, 'balanceRefundMoney');
    }
    public function getBalanceRefundOrderAttribute()
    {
        return $this->sum($this->statisticsDay, 'balanceRefundOrder');
    }

    public function getStoredValueCountAttribute()
    {
        return $this->sum($this->statisticsDay, 'storedValueCount');
    }

    public function getStoredValueCapitalAttribute()
    {
        return $this->sum($this->statisticsDay, 'storedValueCapital');
    }
}
