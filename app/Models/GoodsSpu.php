<?php

namespace App\Models;

use App\Models\Goods\Channel;
use App\Models\Goods\SetmealGoods;
use App\Models\Goods\SpuAttrIds;
use App\Models\Goods\SpuMaterialIds;
use App\Models\Goods\SpuSpecIds;
use App\Models\GoodsActivity\Goods as GoodsActivityGoods;
use App\Models\GoodsRecommend\Goods;
use App\Models\GoodsSearch\Store\GoodsCat;
use App\Models\Recipe\RecipeGoods;
use App\Models\Store\StoreGoods;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GoodsSpu extends BaseModel
{
    protected $table = 'goods_spu';
    use HasFactory, SoftDeletes;
    protected $fillable = ['state', 'storeId', 'setMealSwitch', 'channelIds', 'vipPriceSwitch', 'name', 'desc', 'sort', 'min', 'type', 'catId', 'labelId', 'markId', 'pinYin', 'initialSales', 'sales', 'isExhibition', 'unitId', 'logo', 'cover', 'images', 'video', 'isShow', 'specSwitch', 'attrSwitch', 'materialSwitch', 'salesTimeSwitch', 'salesTimeData', 'salesType', 'orderlimitSwitch', 'orderlimit', 'userlimitSwitch', 'userlimit', 'daylimitSwitch', 'daylimit', 'oneDeliverySwitch', 'shareTitle', 'shareImage', 'shareNotes'];
    protected $casts =  [
        'images' => 'array',
        'catId' => 'array',
        'labelId' => 'array',
        'materialData' => 'array',
        'salesTimeData' => 'array',
        'channelIds' => 'array'
    ];
    public $_timeArr = [];
    public $_inTime = null;

    protected $appends = [
        'specData', 'attrData', 'materialData', 'inTime', 'setmealData', 'isSpec'
    ];

    protected $attributes = [
        "images" => "[]",
    ];


    protected $with = [
        'skus', 'singleSpec', 'category', 'label', 'unit', 'mark', 'discounts', 'content'
    ];

    protected $hidden = ['specs', 'attrs', 'materials', 'setmeal'];

    public function category()
    {
        return $this->belongsToMany(GoodsCat::class, 'spu_catids', 'spuId', 'catId')->orderBy('sort', 'asc')->orderBy('id', 'desc');
    }

    public function channel()
    {
        return $this->hasMany(Channel::class, 'spuId', 'id');
    }

    public function storeGoods()
    {
        return $this->hasMany(StoreGoods::class, 'spuId', 'id');
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

    public function skus()
    {
        return $this->hasMany(GoodsSku::class, 'spuId', 'id')->where('type', 2);
    }

    public function singleSpec()
    {
        return $this->hasOne(GoodsSku::class, 'spuId', 'id')->where('type', 1);
    }

    public function setmeal()
    {
        return $this->hasMany(SetmealGoods::class, 'spuId', 'id')->with(['goods'=> function ($q) {
            return $q->orderBy('sort', 'asc');
        }]);
    }

    public function discounts()
    {
        return $this->hasMany(GoodsActivityGoods::class, 'spuId', 'id')
            ->where("startTime", "<=", Carbon::now()->toDateTimeString())
            ->where("endTime", ">=", Carbon::now()->toDateTimeString())
            ->where('state', 1)
            ->groupBy('type');
    }

    public function content()
    {
        return $this->hasOne(GoodsContent::class, 'spuId', 'id');
    }


    public function scopeShelf($q)
    {
        return $q->where('state', 1);
    }

    public function scopeOffShelf($q)
    {
        return $q->where('state', 0);
    }

    public static  function scopeStateCount($q)
    {
        return $q->select(DB::raw("IFNULL(sum(if(state = 1 and deleted_at is null,1,0)),0) as shelfNum,
        IFNULL(sum(if(state = 0 and deleted_at is null,1,0)),0) as shelfOffNum,
        IFNULL(sum(if(deleted_at is not null,1,0)),0) as
        recycleNum"))->withTrashed();
    }

    public function recipeGoods()
    {
        return $this->hasMany(RecipeGoods::class, 'spuId', 'id');
    }

    public function recommendGoods()
    {
        return $this->hasMany(Goods::class, 'spuId', 'id');
    }

    public function specs()
    {
        return $this->hasMany(SpuSpecIds::class, 'spuId', 'id');
    }

    public function attrs()
    {
        return $this->hasMany(SpuAttrIds::class, 'spuId', 'id');
    }

    public function materials()
    {
        return $this->hasMany(SpuMaterialIds::class, 'spuId', 'id');
    }

    public function getSpecDataAttribute()
    {
        return collect($this->specs)->map(function ($item, $key) {
            $data = collect($item->spec)->toArray();
            $data['value'] = $item->value;
            $data['checkList'] = collect($data['value'])->pluck('id')->all();
            return $data;
        });
    }

    public function getSetmealDataAttribute()
    {
        return [
            'fix' => collect($this->setmeal)->where('type', 1)->values(),
            'match' => collect($this->setmeal)->where('type', 2)->sortBy('sort')->values(),
        ];
    }

    public function getAttrDataAttribute()
    {
        return collect($this->attrs)->map(function ($item, $key) {
            $data = collect($item->attr)->toArray();
            $data['state'] = $item->state;
            $data['value'] = $item->value;
            $data['checkList'] = collect($data['value'])->pluck('id')->all();
            return $data;
        });
    }

    public function getMaterialDataAttribute()
    {
        return collect($this->materials)->map(function ($item, $key) {
            $data = collect($item->materialCat)->toArray();
            $data['materialList'] = $item->materialList;
            $data['required'] = $item->required;
            $data['maxNum'] = $item->maxNum;
            $data['astrict'] = $item->astrict;
            $data['checkList'] = collect($data['materialList'])->pluck('id')->all();
            return $data;
        });
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
    public function getIsSpecAttribute()
    {
        return  intval($this->specSwitch || $this->attrSwitch || $this->materialSwitch || $this->setMealSwitch);
    }
}
