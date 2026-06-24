<?php

namespace App\Models\ExchangeCode;

use App\Models\BaseModel;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ExchangeCodeReceive extends BaseModel
{
    use HasFactory;
    protected $table = 'exchange_code_receive';
    protected $fillable = [
        'uniacid', 'userId', 'exchangeCodeId', 'type', 'sn', 'state', 'display','storeId'
    ];
    protected $appends = [
        'stateFormat'
    ];

    public function exchangeCode()
    {
        return $this->hasOne(ExchangeCode::class, 'id', 'exchangeCodeId');
    }

    public function member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function getInventoryAttribute()
    {
        $key = "exchangCode:{$this->exchangeCodeId}";
        $issusKey = "exchangCodeUse:{$this->exchangeCodeId}";
        $issusCount = Cache::get($issusKey, 0);
        $inventory = $this->exchangeCode->num ?? 0;
        if (!Cache::has($key)) {
            Cache::set($key, $inventory - $issusCount);
        }
        return Cache::get($key);
    }

    public function subInventory($num = 1)
    {
        $key = "exchangCode:{$this->exchangeCodeId}";
        $issusKey = "exchangCodeUse:{$this->exchangeCodeId}";
        $count = Cache::get($key);
        $issusCount = Cache::get($issusKey, 0);
        Cache::set($key, ($count - $num));
        Cache::set($issusKey, ($issusCount + $num));
        return true;
    }

    public function getUserDayLimitAttribute()
    {
        $user = auth('user')->user();
        $dayLimitKey = "exchangCode:userDaylimit:{$this->exchangeCodeId}" . date("Ymd") . ":{$user->id}";
        if (!Cache::has($dayLimitKey)) {
            Cache::set($dayLimitKey, 0);
        }
        return Cache::get($dayLimitKey);
    }

    public function getUserLimitAttribute()
    {
        $user = auth('user')->user();
        $limitKey = "exchangCode:userlimit:{$this->exchangeCodeId}{$user->id}";
        if (!Cache::has($limitKey)) {
            Cache::set($limitKey, 0);
        }
        return Cache::get($limitKey);
    }

    public function getStateFormatAttribute()
    {
        $data = [0 => '已作废', 1 => '未兑换', 2 => '已兑换'];
        return $data[$this->state];
    }
}
