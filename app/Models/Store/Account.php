<?php

namespace App\Models\Store;

use App\Models\Store;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    protected $table = 'store_account';
    use HasFactory;
    protected $guarded = [];
    protected $casts =  [
        'rateConfig' => 'array',
        'withdrawalConfig' => 'array'
    ];
    protected $attributes = [
        'amount' => 0,
        'withdrawalAmount' => 0,
        'withdrawalCompleteAmount' => 0,
        'freezeAmount' => 0,
        'refundOfAmount' => 0,
        'refundAmount' => 0,
    ];
    protected $appends = [
        'total_amount', 'rateConfig'
    ];
    public function getTotalAmountAttribute()
    {
        return bcadd(bcadd(bcadd($this->amount, $this->withdrawalAmount, 2), $this->freezeAmount, 2), $this->refundOfAmount, 2);
    }
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name', 'payChange']);
    }


    public static  function scopeMoney($query)
    {
        return $query->select(DB::raw("IFNULL(sum(if(id > 0 ,1,0)),0) as shopCount,IFNULL(sum(if(id > 0 ,amount,0)),0) as amount,IFNULL(sum(if(id > 0,withdrawalAmount,0)),0) as withdrawalAmount,IFNULL(sum(if(id > 0 ,withdrawalCompleteAmount,0)),0) as withdrawalCompleteAmount"));
    }

    public function getRateConfigAttribute($value)
    {
        if (empty($this->attributes['rateConfig'])) {
            $data = ConfigService::getChannelConfig('serviceRate', $this->uniacid);
            return $data ? $data['rateConfig'] : null;
        }
        $data = json_decode($this->attributes['rateConfig'], true);
        return  isset($data['rateConfig']) ? $data['rateConfig'] : $data;
    }
}
