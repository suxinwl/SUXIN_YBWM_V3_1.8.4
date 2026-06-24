<?php

namespace App\Models\GoodsSearch\Store;

use App\Models\BaseModel;
use App\Models\Goods\Channel;
use App\Models\Goods\SpuAttrIds;
use App\Models\Goods\SpuMaterialIds;
use App\Models\Goods\SpuSpecIds;
use App\Models\GoodsLabel;
use App\Models\GoodsMark;
use App\Models\GoodsRecommend\Goods;
use App\Models\GoodsSku;
use App\Models\GoodsSpu as ModelsGoodsSpu;
use App\Models\GoodsUnit;
use App\Models\Recipe\RecipeGoods;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InStoreGoods extends BaseModel
{
    protected $table = 'goods_spu';
    use HasFactory, SoftDeletes;
    public $_timeArr = [];
    public $_inTime = null;
    protected $casts =  [
        'images' => 'array',
        'catId' => 'array',
        'labelId' => 'array',
        'materialData' => 'array',
        'salesTimeData' => 'array',
        'channelIds' => 'array'
    ];
    protected $appends = [
        'inTime', 'isSpec',
    ];
    protected $with = [
        'skus', 'singleSpec', 'category', 'label', 'unit', 'mark'
    ];
    protected $attributes = [
        "images" => "[]",
    ];
    protected $hidden = ['specs', 'attrs', 'materials', 'images', 'content'];

    public function goodsSkus()
    {
        return $this->hasMany(GoodsSku::class, 'spuId', 'id');
    }

    public function skus()
    {
        return $this->hasMany(StoreGoodsSku::class, 'spuId', 'id')->where('type', 2);
    }


    public function singleSpec()
    {
        return $this->hasOne(StoreGoodsSku::class, 'spuId', 'id')->where('type', 1);
    }

    public function getIsSpecAttribute()
    {
        return  $this->specSwitch || $this->attrSwitch || $this->materialSwitch || $this->setMealSwitch || $this->type == 2;
    }

    public function category()
    {
        return $this->belongsToMany(GoodsCat::class, 'spu_catids', 'spuId', 'catId')->orderBy('sort', 'asc')->orderBy('id', 'desc');
    }

    public function channel()
    {
        return $this->hasMany(Channel::class, 'spuId', 'id');
    }

    public function label()
    {
        return $this->belongsToMany(GoodsLabel::class, 'spu_labels', 'spuId', 'labelId');
    }

    public function unit()
    {
        return $this->hasOne(GoodsUnit::class, 'id', 'unitId');
    }

    public function mark()
    {
        return $this->hasOne(GoodsMark::class, 'id', 'markId');
    }

    public function getTimeArrAttribute()
    {
        if (!$this->_timeArr) {
            $key = "goodsTimeArr:{$this->id}";
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
            } else {
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
}
