<?php

namespace App\Models\Statistics;

use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Member\Vip;
use App\Models\Order\TakeOutOrder;
use App\Models\StatisticsDay;
use App\Traits\StatisticsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class StoredValueFinance extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    protected $fillable  = [
        'uniacid'
    ];
    protected  $appends = ['statisticsDay'];

    /**
     * 查询统计数据
     */
    public function getStatisticsDayAttribute()
    {
        if (!$this->_statisticsDay) {
            $storeId = $this->storeId;
            $timeArr = $this->timeArr();
            $query = StatisticsDay::select([
                'id', 'uniacid', 'day', 'storeId',
                DB::raw('sum(newMember) as newMember'),
                DB::raw('sum(balanceMoney) as balanceMoney'),
                DB::raw('sum(payMember) as payMember'),
                DB::raw('sum(balanceRefundMoney) as balanceRefundMoney'),
                DB::raw('sum(balanceRefundOrder) as balanceRefundOrder'),
                DB::raw('sum(sellMoney) as sellMoney'),
                DB::raw('sum(money) as money'),
                DB::raw('sum(discountMoney) as discountMoney'),
                DB::raw('sum(discountMoney) as discountMoney'),
            ]);
            $this->_statisticsDay = $query->where('uniacid', $this->uniacid)
                ->when($storeId, function ($q) use ($storeId) {
                    if (is_array($storeId)) {
                        $q->whereIn('storeId', $this->storeId);
                    } else {
                        $q->where('storeId', $this->storeId);
                    }
                })
                ->whereBetween('day', [$timeArr['startTime'], $timeArr['endTime']])
                ->limit(30)
                ->groupBy('day')
                ->get();
        }
        return  $this->_statisticsDay;
    }
}
