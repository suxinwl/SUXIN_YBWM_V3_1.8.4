<?php

namespace App\Models\Drinks;

use App\Models\BaseModel;
use App\Models\Store;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrinksBase extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'drinks_storage';
    protected $appends = [
        'statistics'
    ];
    protected $casts =  [
        'storeIds' => 'array',
    ];
    public function getStatisticsAttribute()
    {
        return DB::table('drinks_log')->select([
            DB::raw("IFNULL(sum(if(`type` = 1,num,0)),0) as depositCount"),
            DB::raw("IFNULL(sum(if(`type` = 2,num,0)),0) as fetchCount"),
            DB::raw("IFNULL(sum(if(`type` = 3,num,0)),0) as expiredCount"),
        ])
            ->where('state', 1)
            ->where('drinksId', $this->id)
            ->first();
    }
    public function getStateFormatAttribute()
    {
        $data = [
            1 => '启用',
            0 => "禁用"
        ];
        return $data[$this->state];
    }
}
