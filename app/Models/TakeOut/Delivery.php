<?php

namespace App\Models\TakeOut;

use App\Models\BaseModel;
use App\Models\Delivery\Rule;
use App\Models\Delivery\Store as DeliveryStore;
use App\Models\Material;
use App\Models\Member\Address;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Services\ConfigService;
use Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\Redis;
use Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Delivery extends BaseModel
{

    // public $priceType = null; //规则类型
    // public $rule = null;  //配送规则
    // public $startMoney = 0; //配送起始金额
    // public $startCount = 0; //配送起始数量
    // public $startRule  = null; //起始配送规则
    //public $money = 0; //配送费
    // public $originalMoney = 0; //原始配送费
    // public $discount = null; //配送费活动
    // public $discountMoney = 0; //活动优惠金额
    // public $address = null; //配送结束地址
    // public $store = null; //配送开始地址
    // public $distance = null; //配送距离
    public $state = 1; //配送距离
    public $msg = '';
    public $_distance = 0;
    public $_rideDistance;
    //配送距离

    protected $guarded = [];
    protected $hidden = [
        'uniacid', 'storeId', 'lat', 'lng', 'addressId', 'uniacid', 'store', 'storeRule'
    ];
    protected $appends = [
        'startRule', 'money', 'state', 'msg', 'minutes', 'km', 'distance'
    ];

    /**
     * 获取配送规则
     */
    public function storeRule()
    {
        return $this->hasOne(DeliveryStore::class, 'storeId', 'storeId')
            ->where('uniacid', $this->uniacid)
            ->select(['kmMinutes', 'kmPushMinutes', 'deliveryType', 'channel', 'startRule', 'km', 'priceType', 'priceFixData', 'priceDistanceData', 'priceAreaData', 'deliveryData']);
    }

    public function getPriceTypeAttribute()
    {
        return $this->storeRule->priceType;
    }

    public function getStartRuleAttribute()
    {
        return $this->storeRule->startRule;
    }


    public function getStartCountAttribute()
    {
        return $this->startRule ? $this->startRule['type'] == 2 ? $this->startRule['value2'] : 0 : 0;
    }

    public function getRuleAttribute()
    {
        if ($this->storeRule->priceType == 1) {
            return  $this->storeRule->priceFixData;
        }
        if ($this->storeRule->priceType == 2) {
            return  $this->storeRule->priceDistanceData;
        }
        if ($this->storeRule->priceType == 3) {
            return  $this->storeRule->priceAreaData;
        }
        if ($this->storeRule->priceType == 4) {
            return  $this->storeRule->priceDistanceData;
        }
    }


    /**
     * 获取配送地址
     */
    public function getAddressAttribute()
    {
        if ($this->addressId) {
            return Address::select(['lat', 'lng'])->where("uniacid", $this->uniacid)->where('id', $this->addressId)->first();
        }
        if ($this->lat && $this->lng) {
            $address =  new Address();
            $address->lat = $this->lat;
            $address->lng = $this->lng;
            return $address;
        }
    }


    public function getMinutesAttribute()
    {
        if ($this->distance < 3) {
            return  $this->storeRule->kmMinutes;
        }
        return  $this->storeRule->kmMinutes +  ceil(bcmul(bcsub($this->distance, 3), $this->storeRule->kmPushMinutes, 0));
    }

    /**
     * 获取门店信息
     */
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId')->select(['id', 'name', 'lat', 'lng']);
    }
    /**
     * 获取距离
     */
    public function getDistanceAttribute()
    {
        if (!$this->_distance) {
            $store = $this->store;
            $address = $this->address;
            if ($address) {
                $key = md5($this->storeId . $this->uniacid . $this->addressId . time());
                Redis::geoAdd($key, $address->lng, $address->lat, 'start');
                Redis::geoAdd($key, $store->lng, $store->lat, 'end');
                $dis =  Redis::geoDist($key, 'start', 'end', 'km');
                Redis::delete($key);
                $this->_distance = round($dis, 2);
            }
            if ($this->priceType == 4) {
                $key = "{$address->lat}:{$address->lng}:qx";
                if (FacadesCache::has($key)) {
                    $this->_distance = FacadesCache::get($key);
                } else {
                    $config = ConfigService::getChannelConfig('basicSetting', $this->uniacid);
                    $config = collect($config)->toArray();
                    if ($config && $config['txKey']) {
                        $res = httpRequest("https://apis.map.qq.com/ws/direction/v1/bicycling/", [
                            'key' => $config['txKey'],
                            'from' => "{$store->lat},{$store->lng}",
                            'to' => "{$address->lat},{$address->lng}"
                        ], [], 'get');
                        if ($res['status'] == 0) {
                            $this->_distance = bcdiv($res['result']['routes'][0]['distance'], 1000, 2);
                            FacadesCache::set($key, $this->_distance, 15 * 60);
                        }
                    }
                }
            }
        }
        return $this->_distance;
    }


    /**
     * Undocumented function
     *获取配送费活动
     * @return void
     */

    public function getDiscountsAttributes()
    {
        return null;
    }

    /**
     * 设置配送
     */
    private function errMsg($msg = '', $money = 0, $startMoney = 0, $km = 0)
    {
        return ['state' => 0, 'msg' => $msg, 'money' => $money, 'startMoney' => $startMoney, 'km' => $km];
    }

    private function returnMoney($money = '', $startMoney, $km = 0)
    {
        return ['state' => 1, 'msg' => '', 'money' => $money, 'startMoney' => $startMoney, 'km' => $km];
    }

    /**
     * 计算配送费
     */
    public function getFeeAttribute()
    {
        if ($this->diningType==1) {
            return $this->returnMoney(0, 0);
        }
        $storeInfo=Store::where('id',$this->storeId)->first();
        if ($this->priceType == 1) {
            $money =  $this->rule['money'];
            $km = $this->storeRule->km;
            $startMoney =   $this->startRule ? $this->startRule['type'] == 1 ? $this->startRule['value'] : null : null;


            if(!$storeInfo->differentPlacesSwitch){
                if (!$this->is_point_in_circle($this->point, $this->circle)) {
                    return $this->errMsg('超出配送范围', $money, $startMoney, $km);
                }
            }

        }
        if ($this->priceType == 2 || $this->priceType == 4) {
            $km = $this->storeRule->km;
            $pushKm = bcsub($this->distance, $this->rule['startKm'],2) <= 0 ? 0 : bcsub($this->distance, $this->rule['startKm'], 2);
            if ($pushKm == 0) {
                $money = $this->rule['startMoney'];
            } else {
                if($pushKm>0&&$pushKm<1){
                    $pushKm=1;
                }
                $pushNum = ceil(bcdiv($pushKm, intval($this->rule['pushKm']), 1));
                $money =   bcadd($this->rule['startMoney'], bcmul($this->rule['pushMoney'], $pushNum, 2), 2);
            }
            $startMoney =   $this->startRule ? $this->startRule['type'] == 1 ? $this->startRule['value'] : null : null;
            if (!$this->is_point_in_circle($this->point, $this->circle)) {
                if(!$storeInfo->differentPlacesSwitch){
                    return $this->errMsg('超出配送范围', $money, $startMoney, $km);
                }
            }
        }

        if ($this->priceType == 3) {
            $rule = $this->getAreaDeliveryMoney();
            if (empty($rule)) {
                if(!$storeInfo->differentPlacesSwitch){
                    return $this->errMsg('超出配送范围');
                }
            }
            $money =  $rule['money'];
            $km = number_format($rule['radius'] / 1000, 2);
            $startMoney =   $rule['startMoney'];
        }
        if ($this->startRule['type'] == 1 && $startMoney > $this->goodsMoney) {
            return $this->errMsg('还差￥' . bcsub($startMoney, $this->goodsMoney, 2) . '起送', $money, $startMoney, $km);
        }
        if ($this->startRule['type'] == 2 && $this->startCount > $this->goodsCount) {
            return $this->errMsg('还差' . bcsub($this->startCount, $this->goodsCount) . '件商品起送', $money, $startMoney, $km);
        }
        return $this->returnMoney($money, $startMoney, $km);
    }

    /**
     * @param $point
     * @param $circle
     * @return bool
     *cancel
     * 判断一个坐标是否在圆内
     * 思路：判断此点的经纬度到圆心的距离  然后和半径做比较
     * 如果此点刚好在圆上 则返回true
     * @param $point ['lng'=>'','lat'=>''] array指定点的坐标
     * @param $circle array ['center'=>['lng'=>'','lat'=>''],'radius'=>'']  中心点和半径
     */
    public function is_point_in_circle($point, $circle)
    {
        $distance = $this->distance($point['lat'], $point['lng'], $circle['center']['lat'], $circle['center']['lng']);
        if ($distance <= $circle['radius']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * * 判断一个坐标是否在一个多边形内（由多个坐标围成的）
     * 基本思想是利用射线法，计算射线与多边形各边的交点，如果是偶数，则点在多边形外，否则
     * 在多边形内。还会考虑一些特殊情况，如点在多边形顶点上，点在多边形边上等特殊情况。
     * @param $point
     * @param $pts
     * @return bool
     * @param $point [指定点坐标]
     * @param $pts [多边形坐标 顺时针方向]
     */
    public function is_point_in_polygon($point, $pts)
    {
        $N = count($pts);
        $boundOrVertex = true; //如果点位于多边形的顶点或边上，也算做点在多边形内，直接返回true
        $intersectCount = 0; //cross points count of x
        $precision = 2e-10; //浮点类型计算时候与0比较时候的容差
        $p1 = 0; //neighbour bound vertices
        $p2 = 0;
        $p = $point;

        $p1 = $pts[0];
        for ($i = 1; $i <= $N; ++$i) {
            if ($p['lng'] == $p1['lng'] && $p['lat'] == $p1['lat']) {
                return $boundOrVertex; //p is an vertex
            }

            $p2 = $pts[$i % $N]; //right vertex
            if ($p['lat'] < min($p1['lat'], $p2['lat']) || $p['lat'] > max($p1['lat'], $p2['lat'])) {
                $p1 = $p2;
                continue; //next ray left point
            }

            if ($p['lat'] > min($p1['lat'], $p2['lat']) && $p['lat'] < max($p1['lat'], $p2['lat'])) {
                if ($p['lng'] <= max($p1['lng'], $p2['lng'])) {
                    if ($p1['lat'] == $p2['lat'] && $p['lng'] >= min($p1['lng'], $p2['lng'])) {
                        return $boundOrVertex;
                    }

                    if ($p1['lng'] == $p2['lng']) {
                        if ($p1['lng'] == $p['lng']) {
                            return $boundOrVertex;
                        } else {
                            ++$intersectCount;
                        }
                    } else {
                        $xinters = ($p['lat'] - $p1['lat']) * ($p2['lng'] - $p1['lng']) / ($p2['lat'] - $p1['lat']) + $p1['lng'];
                        if (abs($p['lng'] - $xinters) < $precision) {
                            return $boundOrVertex;
                        }

                        if ($p['lng'] < $xinters) {
                            ++$intersectCount;
                        }
                    }
                }
            } else {
                if ($p['lat'] == $p2['lat'] && $p['lng'] <= $p2['lng']) {
                    $p3 = $pts[($i + 1) % $N]; //next vertex
                    if ($p['lat'] >= min($p1['lat'], $p3['lat']) && $p['lat'] <= max($p1['lat'], $p3['lat'])) {
                        ++$intersectCount;
                    } else {
                        $intersectCount += 2;
                    }
                }
            }
            $p1 = $p2;
        }

        if ($intersectCount % 2 == 0) {
            //偶数在多边形外
            return false;
        } else {
            //奇数在多边形内
            return true;
        }
    }

    public function getPointAttribute()
    {
        return   ['lng' => $this->address->lng, 'lat' => $this->address->lat];
    }

    public function getCircleAttribute()
    {
        return [
            'center' => ['lng' => $this->store->lng, 'lat' => $this->store->lat],
            'radius' => $this->storeRule->km * 1000
        ];
    }
    //计算配送费(按区域)
    public function getAreaDeliveryMoney()
    {
        $point = $this->point;
        $rule = collect($this->rule)->sortBy('radius')->map(function ($item) use ($point) {
            if ($item['shape'] == 1) {
                $circle = [
                    'center' => ['lng' => $item['lng'], 'lat' => $item['lat']],
                    'radius' => $item['radius'],
                ];
                return $this->is_point_in_circle($point, $circle) ? $item : [];
            }
            if ($item['shape'] == 2) {
                $pts = [];
                for ($k = 0; $k < count($item['details']); $k++) {
                    $pts[] = ['lng' => $$item['details'][$k]['lng'], 'lat' => $item['details'][$k]['lat']];
                }
                return $this->is_point_in_polygon($point, $pts) ? $item : [];
            }
        })->reject(function ($item) {
            return empty($item);
        })->toArray();
        return empty($rule) ? null : $rule[0];
    }

    /**
     *  计算两个点之间的距离
     * @param $latA  [第一个点的纬度]
     * @param $lonA  [第一个点的经度]
     * @param $latB  [第二个点的纬度]
     * @param $lonB  [第二个点的经度]
     * @return float
     */
    public function distance($latA, $lonA, $latB, $lonB)
    {
        $earthR = 6371000.;
        $PI = 3.14159265358979324;
        $x = cos($latA * $PI / 180.) * cos($latB * $PI / 180.) * cos(($lonA - $lonB) * $PI / 180);
        $y = sin($latA * $PI / 180.) * sin($latB * $PI / 180.);
        $s = $x + $y;
        if ($s > 1) {
            $s = 1;
        }

        if ($s < -1) {
            $s = -1;
        }

        $alpha = acos($s);
        $distance = $alpha * $earthR;
        return $distance;
    }

    public function getStateAttribute()
    {
        return $this->fee['state'];
    }

    public function getMsgAttribute()
    {
        return $this->fee['msg'];
    }

    public function getMoneyAttribute()
    {
        return $this->fee['money'];
    }

    public function getStartMoneyAttribute()
    {
        return $this->fee['startMoney'];
    }

    public function getKmAttribute()
    {
        return $this->fee['km'];
    }
}
