<?php

namespace App\Models\Tables;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Type extends BaseModel
{
    use HasFactory;
    protected $table = 'table_type';
    protected $fillable = [
        'uniacid', 'storeId', 'name', 'sort', 'queueSwitch', 'queueMinutes', 'reserveSwitch', 'orderDishes', 'reserveMoney',
        'serialNum', 'waitTime', 'minNum', 'maxNum', 'earnest', 'orderswitch','max'
    ];

    protected $attributes = [
        'earnest' => 0
    ];

    public function getQueuingUpAttribute()
    {
        $count =  DB::table('queuing_up')
            ->whereNull('deleted_at')
            ->where('uniacid', $this->uniacid)
            ->where('storeId', $this->storeId)
            ->where('type_id', $this->id)
            ->where('state', 1)
            ->count();
        $key = "serialNum:{$this->uniacid}:{$this->storeId}:{$this->id}";
        $tag = "serialNum:store:{$this->storeId}";
        $quhao = Cache::tags($tag)->get($key);
        $start =   DB::table('queuing_up')
            ->whereNull('deleted_at')
            ->where('uniacid', $this->uniacid)
            ->where('storeId', $this->storeId)
            ->where('type_id', $this->id)
            ->where('state', 2)
            ->orderBy('updated_at', 'desc')
            ->first();
        return [
            'count' => $count,
            'start' => $start ? $start->serialNum : null,
            'minutes' => $count * $this->waitTime,
            'quhao' => $quhao ? $this->serialNum . $quhao : null,
        ];
    }
}
