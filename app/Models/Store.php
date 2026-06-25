<?php

namespace App\Models;

use App\Enums\SceneEnum;
use App\Models\FullSub\FullSub;
use App\Models\GoodsRecommend\Store as GoodsRecommendStore;
use App\Models\NewSub\NewSub;
use App\Models\Recipe\RecipeStore;
use App\Models\Store\Account;
use App\Models\Store\Notice;
use App\Models\StoreConfig;
use App\Models\TakeOut\Delivery;
use App\Services\ConfigService;
use App\Services\DataSeederService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;

class Store extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $_storeSetting;
    protected $_news;
    protected $_delivery;
    protected $_timeArr;
    protected $_realtimeState;
    protected $_fullsub;
    protected $_newSub;
    protected $_deliverySub;
    protected $_inStoreSetting;
    protected $_takeScreenSetting;
    protected $table = 'store';
    protected $fillable = ['name', 'isolate', 'pickupSwitch', 'groupId', 'uniacid', 'sort', 'storeSn', 'surroundings', 'region', 'address', 'lat', 'lng', 'contact', 'mobile', 'storeMobile', 'labelId', 'isShowSwitch', 'operatingStatus', 'businessStatus', 'businessData', 'shareImg', 'shareTitle', 'businessLicense', 'tradeLicense', 'takeoutSwitch', 'inStoreSwitch', 'paySwitch', 'expressSwitch', 'differentPlacesSwitch'];
    protected $casts =  [
        'surroundings' => 'array',
        'businessData' => 'array',
        'businessLicense' => 'array',
        'tradeLicense' => 'array',
        'region' => 'array',
        'labelId' => 'array',
    ];
    protected $appends = [
        'distance', 'timeArr', 'realtimeState', 'distanceNum', 'inStoreSetting','regionFormat','storeSetting'
    ];

    protected $attributes = [
        'sort' => 0,
        'groupId' => 0,
        'storeSn' => '',
        'isShowSwitch' => 1,
        'operatingStatus' => 1,
        'businessStatus' => 1,
        'takeoutSwitch' => 1,
        'inStoreSwitch' => 1,
        'paySwitch' => 1,
        'expressSwitch' => 0,
        'differentPlacesSwitch' => 0,
        'pickupSwitch' => 1,
        'labelId' => '[]',
    ];


    public function getDistanceAttribute()
    {
        if ($this->distanceNum !== null) {
            $dist = $this->distanceNum;
            if ($dist < 1000) {
                return number_format($dist, 2) . 'm';
            }
            return number_format(($dist / 1000), 2) . "km";
        }
        return null;
    }

    public function getDistanceNumAttribute()
    {
        if (Request()->lat && Request()->lng) {
            $key = "storeDis:" . $this->id . rand(1000000, 9999999);
            Redis::geoAdd($key, $this->lng, $this->lat, 'start');
            Redis::geoAdd($key, Request()->lng, Request()->lat, 'end');
            $dist = Redis::geoDist($key, 'start', 'end', 'm');
            Redis::delete($key);
            return intval($dist);
        }
        return null;
    }

    public function getStoreSettingAttribute()
    {
        if (!$this->_storeSetting) {
            $config = ConfigService::getStoreConfig('storeSetting', $this->id);
            $this->_storeSetting = empty($config) ? null : $config;
        }
        return $this->_storeSetting;
    }

    public function getInStoreSettingAttribute()
    {
        if (!$this->_inStoreSetting) {
            $config = ConfigService::getStoreConfig('inStoreSetting', $this->id);
            $this->_inStoreSetting = empty($config) ? null : $config;
        }
        return $this->_inStoreSetting;
    }

    public function getTakeScreenSettingAttribute()
    {
        if (!$this->_takeScreenSetting) {
            $config = ConfigService::getStoreConfig('takeScreen', $this->id);
            $this->_takeScreenSetting = empty($config) ? null : $config;
        }
        return $this->_takeScreenSetting;
    }

    public function getQueueingSettingAttribute()
    {
        return ConfigService::getStoreConfig('queuing', $this->id);
    }

    public function getStoreWifiSettingAttribute()
    {
        return ConfigService::getStoreConfig('storeWifi', $this->id);
    }


    public function scopeBusiness($q)
    {
        return $q->where('operatingStatus', 1);
    }

    public function label()
    {
        return $this->belongsToMany(StoreLabel::class, 'store_label_ids', 'storeId', 'labelId');
    }

    public function takeoutCats()
    {
        return $this->belongsToMany(SpuCatgorys::class, 'store_goods', 'storeId', 'spuId')->wherePivot('type', 1);
    }

    public function inStoreCats()
    {
        return $this->belongsToMany(SpuCatgorys::class, 'store_goods', 'storeId', 'spuId')->wherePivot('type', 2);
    }

    public function recipeStore()
    {
        return $this->hasMany(RecipeStore::class, 'storeId', 'id');
    }
    public function recommendStore()
    {
        return $this->hasMany(GoodsRecommendStore::class, 'storeId', 'id');
    }

    public function collectStore()
    {
        return $this->hasOne(Collect::class, 'collectId', 'id')->where("type", 'store')->where('userId', auth('user')->user()->id);
    }



    public function getDeliveryAttribute()
    {
        if (empty($this->_delivery)) {
            $model = new Delivery(
                [
                    "goodsCount" => 0,
                    "goodsMoney" => 0,
                    "uniacid" => $this->uniacid,
                    "storeId" => $this->id,
                    "lat" => Request()->lat,
                    "lng" => Request()->lng,
                    "addressId" => Request()->addressId
                ]
            );
            $this->_delivery = ['deliveryData' => $model->storeRule->deliveryData, 'deliveryType' => $model->storeRule->deliveryType, 'channel' => $model->storeRule->channel, 'km' => $model->km, 'priceType' => $model->priceType, 'startRule' => $model->startRule, 'startMoney' => $model->startMoney, 'money' => $model->money, 'state' => $model->state, 'msg' => $model->msg];
        }
        return $this->_delivery;
    }

    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $smsAccount = Account::create([
                'uniacid' => $model->uniacid,
                'storeId' => $model->id
            ]);
            $data[] = ["h" => 0, 'uniacid' => $model->uniacid, 'day' => date("Y-m-d", time()), 'storeId' => $model->id];
            StatisticsDay::insert($data);
            DataSeederService::StoreConfigSeed($model->uniacid, $model->id);
            DataSeederService::StoreDeliverySeed($model->uniacid, $model->id);
            if ($model->isolate == 1) {
                DataSeederService::applyConfigSeed($model->uniacid, $model->id);
                DataSeederService::applyVipSeed($model->uniacid, $model->id);
                DataSeederService::dragSeed($model->uniacid, $model->id);
                DataSeederService::applyVoiceSeed($model->uniacid, $model->id);
            }
        });
    }
    public function getRegionFormatAttribute()
    {
        if (!empty($this->region)) {
            $list =  Region::select('name')->whereIn('id', $this->region)->get();
            $list = collect($list)->pluck('name')->toarray();
        }
        return empty($list) ? [] : $list;
    }

    public function getGgAttribute()
    {
        if (!$this->_news) {
            $storeId = $this->id;
            $this->_news =  Notice::where('uniacid', $this->uniacid)
                ->where('type', 2)
                ->where('startTime', "<=", date("Y-m-d H:i:s", time()))
                ->where('endTIme', ">=", date("Y-m-d H:i:s", time()))
                ->whereHas('stores', function ($q) use ($storeId) {
                    return $q->where('storeId', $storeId);
                })
                ->first();
        }
        return $this->_news;
    }

    public function getSceneAttribute()
    {
        if (Request()->diningType == 0) {
            return  SceneEnum::SCENE_TAKEOUT;
        }
        return SceneEnum::SCENE_INSTORE;
    }


    public function getTimeArrAttribute()
    {
        if (!$this->_timeArr) {
            $timeArr =  $this->businessData;
            // if ($this->scene == SceneEnum::SCENE_TAKEOUT) {
            //     $timeArr = $this->storeSetting['outTimeData'];
            // }
            if (empty($timeArr)|| !in_array(date("w"), $timeArr['week'])) {
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
            $this->_timeArr = $timeData;
        }
        return $this->_timeArr;
    }

    public function getRealtimeStateAttribute()
    {
        if (!$this->_realtimeState) {
            $this->_realtimeState = 3;
            if ($this->businessStatus == 3) {
                return $this->_realtimeState;
            }
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
                    $this->_realtimeState = 1;
                    break;
                };
            }
            // if ($this->_realtimeState == 3) {
            //     if ($this->scene == SceneEnum::SCENE_TAKEOUT && in_array(2, $this->storeSetting['outStepTime'] ?? [0])) {
            //         $this->_realtimeState = 4;
            //     }
            //     if ($this->scene == SceneEnum::SCENE_INSTORE && in_array(2, $this->storeSetting['takeAppointTimeStep'] ?? [0])) {
            //         $this->_realtimeState = 4;
            //     }
            // }
            if ($this->_realtimeState == 1) {
                $count =  DB::table('takeout_order')
                    ->where('uniacid', $this->uniacid)
                    ->where('storeId', $this->id)
                    ->whereIn('state', [2, 3])
                    ->count();
                if ($count > 10) {
                    $this->_realtimeState = 2;
                }
            }
        }
        return $this->_realtimeState;
    }

    public function getFullSubAttribute()
    {
        $scene = Request()->scene;
        $storeId = $this->id;
        $uniacid = $this->uniacid;
        if (empty($this->_fullsub)) {
            $this->_fullsub = FullSub::where('uniacid', $this->uniacid)
                ->whereIn('type', [1, 2])
                ->where(function ($q) use ($storeId, $uniacid) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->when($this->isolat == 0, function ($q) use ($uniacid) {
                        return $q->orWhere(function ($q) use ($uniacid) {
                            return $q->where('storeType', 1)->where('uniacid', $uniacid)->where('storeId', 0);
                        });
                    });
                })
                ->where('startTime', '<=', date("Y-m-d H:i:s"))
                ->where('endTime', '>=', date("Y-m-d H:i:s"))
                ->first();
        }
        return $this->_fullsub;
    }


    public function getDeliverySubAttribute()
    {
        $scene = Request()->scene;
        $storeId = $this->id;
        $uniacid = $this->uniacid;
        $isolateStore = $this->isolateStore;
        if (empty($this->_deliverySub)) {
            $this->_deliverySub = FullSub::where('uniacid', $this->uniacid)
                ->where('type', 3)
                ->where(function ($q) use ($storeId, $uniacid) {
                    return $q->whereHas('stores', function ($q) use ($storeId, $uniacid) {
                        return $q->where(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId, $uniacid) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    })->when($this->isolat == 0, function ($q) use ($uniacid) {
                        return $q->orWhere(function ($q) use ($uniacid) {
                            return $q->where('storeType', 1)->where('uniacid', $uniacid)->where('storeId', 0);
                        });
                    });
                })
                ->where('startTime', '<=', date("Y-m-d H:i:s"))
                ->where('endTime', '>=', date("Y-m-d H:i:s"))
                ->first();
        }
        return $this->_deliverySub;
    }

    public function getNewSubAttribute()
    {
        $scene = Request()->scene;
        $storeId = $this->id;
        $uniacid = $this->uniacid;
        $user = auth('user')->user();
        $userId = $user->id ?? Request()->userId;
        $isolate = $this->isolate;
        $isolateStore = $this->isolateStore;
        $order = DB::table('order_index')->select('id')->where('userId', $userId)->first();
        if (empty($this->_newSub) && $userId > 0 && empty($order)) {
            $this->_newSub = NewSub::where('uniacid', $uniacid)
                ->where('storeId', $isolate == 1  ? $this->id : 0)
                ->where(function ($q) use ($storeId) {
                    return $q->whereHas('stores', function ($q) use ($storeId) {
                        return $q->where(function ($q) use ($storeId) {
                            return $q->where('storeId', $storeId)->where('type', 2);
                        })->orWhere(function ($q) use ($storeId) {
                            return $q->where('storeId', '!=', $storeId)->where('type', 3);
                        });
                    });
                })->when($this->isolat == 0, function ($q) use ($uniacid) {
                    return $q->orWhere(function ($q) use ($uniacid) {
                        return $q->where('storeType', 1)->where('uniacid', $uniacid)->where('storeId', 0);
                    });
                })
                ->where('startTime', '<=', date("Y-m-d H:i:s"))
                ->where('endTime', '>=', date("Y-m-d H:i:s"))
                ->first();
        }
        return $this->_newSub;
    }

    public function getPayConfigAttribute()
    {
        $model = PayConfig::select(['id', 'payType', 'templateId', 'state', 'channel', 'isDefault'])
            ->where('uniacid', $this->uniacid)
            ->where('storeId', $this->id)
            ->where('channel', 'mini')
            ->get();
        if (!empty($model)) {
            return [
                'pay' => collect($model)->keyBy('payType'),
                'default' => collect($model)->where('isDefault', 1)
                    ->first()->payType,
            ];
        }
        return null;
    }

    public function getIsolateStoreAttribute()
    {
        return  $this->isolate == 1 ? $this->id : 0;
    }
}
