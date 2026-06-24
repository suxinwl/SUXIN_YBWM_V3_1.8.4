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

class StoredValueData extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    protected $fillable  = [
        'uniacid'
    ];

    protected  $appends = ['thard', 'statisticsDay'];

    public function getThardAttribute()
    {
        $storeId = $this->storeId;
        $user = auth('admin')->user();
        $timeArr = $this->timeArr();
        $model =   StatisticsDay::select([
            DB::raw("IFNULL(sum(storedValueCapital),0) as storedValueCapital"),
            DB::raw("IFNULL(sum(storedValueGive),0) as storedValueGive"),
            DB::raw("IFNULL(sum(balanceMoney) + sum(sysSubStoredValue),0) as balanceMoney"),
            DB::raw("IFNULL(sum(storedValue) - sum(sysSubStoredValue) - sum(balanceMoney),0) as balance"),
        ])->where('uniacid', $this->uniacid)
            ->where('storeId', 0)
            ->groupBy('uniacid')
            ->groupBy('day')
            ->first();
        if ($model) {
            $model->setAppends([]);
        }
        return $model;
    }

    /**
     * 查询统计数据
     */
    public function getStatisticsDayAttribute()
    {
        if (!$this->_statisticsDay) {
            $storeId = $this->storeId;
            $user = auth('admin')->user();
            $timeArr = $this->timeArr();
            $this->_statisticsDay = StatisticsDay::select([
                'id',
                'startBalance',
                'storedValueCapital',
                'storedValueGive',
                'balanceMoney',
                'storedValue',
                'day',
                'sysSubStoredValue',
                DB::raw("IFNULL(sysSubStoredValue + balanceMoney,0) as balanceMoney"),
                DB::raw("IFNULL(storedValue + startBalance - sysSubStoredValue - balanceMoney,0) as balance"),
            ])
                ->where('uniacid', $this->uniacid)
                ->where('storeId', 0)
                ->where('day', '>=', $timeArr['startTime'])
                ->where('day', '<=', $timeArr['endTime'])
                ->groupBy('day')
                ->orderBy('day', 'desc')
                ->get();
        }
        return  $this->_statisticsDay;
    }

    
}
