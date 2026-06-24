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

class MemberData extends BaseModel
{
    use StatisticsTrait;
    public $_statisticsDay;
    protected $fillable  = [
        'uniacid', 'storeId'
    ];

    protected  $appends = [
        'userCount', 'toDay', 'channelData', 'vipData', 'userTrend', 'userPayTrend', 'visitorCount', 'activeCount', 'silenceCount'
    ];

    /**
     * 总用户
     */
    public function getUserCountAttribute()
    {
        return Member::where("uniacid", $this->uniacid)
            ->where('storeId', $this->storeId)
            ->where('mobile', "!=", '')->count();
    }

    /**
     * 查询30天的统计数据
     */
    public function getStatisticsDayAttribute()
    {
        if (!$this->_statisticsDay) {
            $this->_statisticsDay = StatisticsDay::where('uniacid', $this->uniacid)
                ->select(['*'])
                ->addSelect([
                    DB::raw('if(newMember < 0 , 0,newMember) as newMember'),
                    DB::raw('if(payMember < 0 , 0,payMember) as payMember'),
                ])
                ->where('storeId', $this->storeId ?? 0)
                ->where('day', "<=", date("Y-m-d", time()))
                ->orderBy('day', 'desc')
                ->limit(30)
                ->get();
            $this->_statisticsDay =   collect($this->_statisticsDay)->sortBy('day');
        }
        return  $this->_statisticsDay;
    }

    /**
     * 获取今天的数据
     */
    public function getToDayAttribute()
    {
        return collect($this->statisticsDay)->where("day", date("m-d", time()))->first();
    }


    /**
     * 访客
     */
    public function getVisitorCountAttribute()
    {
        return Member::where("uniacid", $this->uniacid)
            ->where('storeId', $this->storeId)
            ->where("mobile", "")->count();
    }



    /**
     * 各渠道用户比例
     */
    public function getChannelDataAttribute()
    {
        $list =  Member::select([DB::raw('count(*) as value'), 'score'])
            ->where('mobile', "!=", '')
            ->where('uniacid', $this->uniacid)
            ->where('storeId', $this->storeId)
            ->groupBy('score')
            ->get();
        return collect($list)->map(function ($item) {
            return ['name' => appTypeFormat($item->score), 'value' => $item['value']];
        });
    }

    /**
     * 各Vip等级用户比例
     */
    public function getVipDataAttribute()
    {
        return Vip::select(['name'])->withCount('member as value')
        ->where('uniacid', $this->uniacid)
        ->where('storeId', $this->storeId)
        ->having('value', '>', 0)->get();
    }



    /**
     * 新增会员趋势
     */
    public function getUserTrendAttribute()
    {
        return $this->lineData($this->statisticsDay, ['newMember']);
    }

    /**
     * 活跃会员数
     */
    public function getActiveCountAttribute()
    {
        return Member::where('uniacid', $this->uniacid)
        ->where('storeId', $this->storeId)
        ->where("mobile", "!=", "")->whereDate("lastLogin", ">=", date("Y-m-d", strtotime("-30day")))->count();
    }

    /**
     * 沉默会员数
     */
    public function getSilenceCountAttribute()
    {
        return Member::where('uniacid', $this->uniacid)
        ->where('storeId', $this->storeId)
        ->where("mobile", "!=", "")->whereDate("lastLogin", "<", date("Y-m-d", strtotime("-30day")))->count();
    }

    /**
     * 新增会员趋势
     */
    public function getUserPayTrendAttribute()
    {
        return $this->lineData($this->statisticsDay, ['payMember']);
    }
}
