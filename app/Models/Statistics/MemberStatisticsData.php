<?php

namespace App\Models\Statistics;

use App\Models\BaseModel;
use App\Models\Collect;
use App\Models\Member;
use App\Models\Member\UserPayStore;
use App\Models\Member\Vip;
use App\Models\Order\TakeOutOrder;
use App\Models\StatisticsDay;
use App\Traits\StatisticsTrait;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class MemberStatisticsData extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    protected $fillable  = [
        'uniacid', 'storeId', 'isolate'
    ];

    protected  $appends = [
        'collectCount', 'userCount', 'vipData', 'repurchaseData', 'pv', 'uv', 'payCount', 'pvUvTrend', 'newMember', 'repurchase', 'payMoney', 'newPayUser'
    ];

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
                    DB::raw('if(sum(newMember) < 0 ,0,sum(newMember)) as newMember'),
                    DB::raw('if(sum(payMember) < 0 ,0,sum(payMember)) as payMember'),
                    DB::raw('if(sum(orderCount) < 0 ,0,sum(orderCount)) as orderCount'),
                    DB::raw('if(sum(money) < 0 ,0,sum(money)) as money'),
                    DB::raw('if(sum(sellMoney) < 0 ,0,sum(sellMoney)) as sellMoney'),
                    DB::raw('if(sum(pv) < 0 ,0,sum(pv)) as pv'),
                    DB::raw('if(sum(uv) < 0 ,0,sum(pv)) as uv'),
                    DB::raw('if(sum(newPayUser) < 0 ,0,sum(newPayUser)) as newPayUser'),
                    DB::raw('if(sum(repurchase) < 0 ,0,sum(repurchase)) as repurchase'),
                ]);
            } else {
                $query = StatisticsDay::select([
                    'id', 'uniacid', 'day', 'storeId',
                    DB::raw('if(sum(newMember) < 0 ,0,sum(newMember)) as newMember'),
                    DB::raw('if(sum(payMember) < 0 ,0,sum(payMember))as payMember'),
                    DB::raw('if(sum(orderCount) < 0 ,0,sum(orderCount)) as orderCount'),
                    DB::raw('if(sum(money) < 0 ,0,sum(money)) as money'),
                    DB::raw('if(sum(sellMoney) < 0 ,0,sum(sellMoney)) as sellMoney'),
                    DB::raw('if(sum(pv) < 0 ,0,sum(pv)) as pv'),
                    DB::raw('if(sum(uv) < 0 ,0,sum(pv)) as uv'),
                    DB::raw('if(sum(newPayUser) < 0 ,0,sum(newPayUser)) as newPayUser'),
                    DB::raw('if(sum(repurchase) < 0 ,0,sum(repurchase)) as repurchase'),
                ]);
            }
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
                ->groupBy('day')
                ->get();
        }
        return  $this->_statisticsDay;
    }

    /**
     * 总用户
     */
    public function getUserCountAttribute()
    {
        return $this->sum($this->statisticsDay, 'newMember');
    }

    /**
     * 累计收藏人数
     */
    public function getCollectCountAttribute()
    {
        $storeId = $this->storeId;
        return Collect::where('uniacid', $this->uniacid)
            ->where('type', 'store')
            ->when($storeId, function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('collectId', $this->storeId);
                } else {
                    $q->where('collectId', $this->storeId);
                }
            })->count();
    }





    /**
     * 各Vip等级用户比例
     */
    public function getVipDataAttribute()
    {
        $isolate = $this->isolate;
        $storeId = $this->storeId;
        return Vip::select(['name'])->withCount('member as value')
            ->when($this->isolate, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when(!$this->isolate, function ($q) use ($isolate) {
                return $q->where('storeId', 0);
            })
            ->where('uniacid', $this->uniacid)
            ->having('value', '>', 0)->get();
    }

    /**
     * 购买人数累计
     */
    public function getRepurchaseDataAttribute()
    {
        $storeId = $this->storeId;
        return  UserPayStore::select(DB::raw('IFNULL(sum(if(count > 10 ,1,0)),0) as count10Plus,IFNULL(sum(if(count > 0 and count <= 5,1,0)),0) as count5,IFNULL(sum(if(count > 5 and count <= 10,1,0)),0) as count10'))
            ->where('uniacid', $this->uniacid)
            ->when($storeId, function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $this->storeId);
                } else {
                    $q->where('storeId', $this->storeId);
                }
            })->first();
    }



    /**
     * pv趋势
     */
    public function getPvUvTrendAttribute()
    {
        return $this->lineData($this->statisticsDay, ['pv', 'uv']);
    }

    /**
     * 下单人数（人）
     */
    public function getPayCountAttribute()
    {
        return $this->sum($this->statisticsDay, 'payMember');
    }

    /**
     * 下单人数（人）
     */
    public function getNewPayUserAttribute()
    {
        return $this->sum($this->statisticsDay, 'newPayUser');
    }

    /**
     * 客单价（元）
     */
    public function getPayMoneyAttribute()
    {
        $strat = $this->sum($this->statisticsDay, 'money');
        $end  = $this->sum($this->statisticsDay, 'payCount');
        if (empty($strat) || empty($end)) {
            return 0.00;
        }
        return  bcdiv($strat, $end, 2);
    }


    /**
     * 老用户复购率（人）
     */
    public function getRepurchaseAttribute()
    {
        return $this->sum($this->statisticsDay, 'repurchase');
    }

    /**
     * 新增会员（人）
     */
    public function getNewMemberAttribute()
    {
        return $this->sum($this->statisticsDay, 'newMember');
    }

    /**
     * pv
     */
    public function getPvAttribute()
    {
        return $this->sum($this->statisticsDay, 'pv');
    }
    /**
     * uv
     */
    public function getUvAttribute()
    {
        return $this->sum($this->statisticsDay, 'uv');
    }
}
