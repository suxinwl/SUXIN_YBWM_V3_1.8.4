<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StatisticsDay extends BaseModel
{
    protected $table = 'statistics_day';
    use HasFactory;
    protected $guarded = [];

    protected $appends = [
        'payMoney', 'H', 'day'
    ];

    /**
     * 客单价
     */
    public function getPayMoneyAttribute()
    {
        if ($this->money <= 0 || $this->payCount == 0) {
            return '0.00';
        }
        return bcdiv($this->money, $this->payCount, 2);
    }

    public function getDayAttribute()
    {
        return date("m-d", strtotime($this->attributes['day']));
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name']);
    }

    public function getIncomeAttribute()
    {
        $list =  MemberAccountLog::select(['behavior', 'type'])
            ->addSelect(DB::raw("TRUNCATE(IFNULL(sum(value),0),2) as giveBalance,count(id) as count"))
            ->where('cat', 'balance')
            ->where('behavior', '!=', MemberAccountLog::BALANCE_GIVE)
            ->where('type', 1)
            ->where('created_at', '>=', $this->attributes['day'] . ' 00:00:00')
            ->where('created_at', '<=', $this->attributes['day'] . ' 23:59:59')
            ->where('uniacid', $this->uniacid)
            ->groupBy('behavior')
            ->get();
        $list = collect($list)->map(function ($item, $key) {
            $item = collect($item)->toArray();
            $item['balance'] = 0;
            if ($item['behavior'] == MemberAccountLog::BALANCE_BUY) {
                $item['balance'] = $item['giveBalance'];
                $give = MemberAccountLog::select(['behavior', 'type'])
                    ->addSelect(DB::raw("TRUNCATE(IFNULL(sum(value),0),2) as giveBalance"))
                    ->where('cat', 'balance')
                    ->where('behavior', MemberAccountLog::BALANCE_GIVE)
                    ->where('type', 1)
                    ->where('created_at', '>=', $this->attributes['day'] . ' 00:00:00')
                    ->where('created_at', '<=', $this->attributes['day'] . ' 23:59:59')
                    ->where('uniacid', $this->uniacid)
                    ->groupBy('behavior')
                    ->first();
                $item['giveBalance'] = $give->giveBalance;
            }
            return $item;
        });
        return $list;
    }

    public function getSpendingAttribute()
    {
        return MemberAccountLog::select(['behavior', 'type'])
            ->addSelect(DB::raw("TRUNCATE(IFNULL(sum(value),0),2) as balance,count(id) as count"),)
            ->where('cat', 'balance')
            ->where('type', 0)
            ->where('created_at', '>=', $this->attributes['day'] . ' 00:00:00')
            ->where('created_at', '<=', $this->attributes['day'] . ' 23:59:59')
            ->where('uniacid', $this->uniacid)
            ->groupBy('behavior')
            ->get();
    }
    public function getHAttribute()
    {
        return $this->attributes['h'] . ":00";
    }

    public function getBalanceMoney()
    {
        return  StatisticsOrder::where('uniacid', $this->uniacid)
            ->whereIn('state', [6, 10])
            ->where('created_at', '>=', $this->attributes['day'] . " 00:00:00")
            ->where('created_at', '<=', $this->attributes['day'] . " 23:59:59")
            ->where('payType', 0)
            ->sum('money');
    }

    public function getBalanceCount()
    {
        return  StatisticsOrder::where('uniacid', $this->uniacid)
            ->whereIn('state', [6, 10])
            ->where('created_at', '>=', $this->attributes['day'] . " 00:00:00")
            ->where('created_at', '<=', $this->attributes['day'] . " 23:59:59")
            ->where('payType', 0)
            ->sum('orderCount');
    }
}
