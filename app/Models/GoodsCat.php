<?php

namespace App\Models;

use App\Models\Order\OrderGoods;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GoodsCat extends BaseModel
{
    protected $table = 'goods_cat';
    use HasFactory;
    protected $with = [
        'label'
    ];
    protected $withCount = [
        'goods'
    ];
    protected $casts =  [
        'salesTimeData' => 'array',
    ];
    protected $appends = ['inTime'];
    public $_timeArr = [];
    public $_inTime = null;
    protected $fillable = ['name', 'sort', 'labelId', 'logo', 'isMust', 'notes', 'uniacid', 'salesTimeData', 'salesTimeSwitch'];

    public function label()
    {
        return $this->hasOne(GoodsCatLabel::class, 'id', 'labelId');
    }


    public function goods()
    {
        return $this->hasMany(SpuCatgorys::class, 'catId', 'id');
    }

    public function orderGoods()
    {
        return $this->belongsToMany(OrderGoods::class, 'spu_catids', 'catId', 'spuId', 'id');
    }

    public function gettimeArrAttribute()
    {
        if (!$this->_timeArr) {
            $key = "goodsCat:$this->id:timeArr";
            if (!Cache::has($key)) {
                $timeArr =  $this->salesTimeData;
                if (empty($timeArr) || !in_array(date("w"), $timeArr['week'])) {
                    return false;
                }
                $lowStartTime = null;
                $timeKey = null;
                $lowEndTime = null;
                foreach ($timeArr['times'] as $key => $time) {
                    if ($time['ciri'] && $time['end'] != '00:00') {
                        $timeArr['times'][] = ['start' => $time['start'], 'end' => "00:00"];
                        $timeArr['times'][] = ['start' => "00:00", 'end' => $time['end']];
                        unset($timeArr['times'][$key]);
                    }
                }
                $timeArr['times'] = collect($timeArr['times'])->sortBy('start');
                foreach ($timeArr['times'] as $key => $time) {
                    $startTime = strtotime(date("Y-m-d " . $time['start']));
                    $endTime = $time['end'] == "00:00" ? strtotime(date("Y-m-d " . $time['end'])) + 3600 * 24  : strtotime(date("Y-m-d " . $time['end']));
                    if ($lowStartTime == null && $lowEndTime == null) {
                        $lowStartTime = $startTime;
                        $lowEndTime = $endTime;
                        $timeKey = $key;
                        $timeData[$key] = $time;
                    } else {
                        if ($startTime > $lowEndTime) {
                            $lowStartTime = $startTime;
                            $lowEndTime = $endTime;
                            $timeKey = $key;
                            $timeData[$key] = $time;
                        } else {
                            if ($endTime > $lowEndTime) {
                                $timeData[$timeKey] = ['start' => date("H:i", $lowStartTime), 'end' => $time['end']];
                                $lowEndTime =  $endTime;
                            }
                        }
                    }
                }
                Cache::set($key, $timeData, 3600);
                $this->_timeArr = $timeData;
            }else{
                $this->_timeArr = Cache::get($key);
            }
        }
        return $this->_timeArr;
    }

    public function getInTimeAttribute()
    {
        if ($this->_inTime == null) {
            $this->_inTime = 0;
            if ($this->salesTimeSwitch == 0) {
                $this->_inTime = 1;
            } else {
                $day = date("Y-m-d", time());
                foreach ($this->timeArr as $key => $v) {
                    if ($v['start'] == '00:00' && $v['end'] == "00:00") {
                        $startTime = strtotime($day . ' 00:00:00');
                        $endTime = strtotime($day . ' 23:59:59');
                    } else {
                        $startTime = strtotime($day . $v['start']);
                        if ($v['end'] == "00:00") {
                            $endTime = strtotime($day . ' 23:59:59');
                        } else {
                            $endTime = strtotime($day . $v['end']);
                        }
                    }
                    if ($startTime <= time() && $endTime >= time()) {
                        $this->_inTime = 1;
                        break;
                    };
                }
            }
        }
        return $this->_inTime;
    }
    public function store(){
        return $this->hasOne(StoreBase::class,'id','storeId');
    }
}
