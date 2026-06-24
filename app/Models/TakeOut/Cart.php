<?php

namespace App\Models\TakeOut;

use App\Models\BaseModel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSearch\GoodsSpuBase;
use App\Models\GoodsSpu;
use App\Models\Material;
use App\Models\Member;
use App\Models\Order\OrderGoods;
use App\Models\SpuCatgorys;
use App\Models\Store\GoodsList;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsBase;
use App\Models\Store\StoreGoodsSku;
use App\Models\Store\StoreGoodsSkuBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Illuminate\Support\Carbon;
class Cart extends BaseModel
{
    protected $table = 'takeout_cart';
    public $_spu;
    public $_sku;
    use HasFactory;
    protected $fillable = [
        'spuId', 'specMd5', 'attrData', 'equityCardMemberId', 'materialData', 'discountType', 'discountMoney', 'discountPrice', 'discountNum', 'discountLabel', 'setMealData', 'setMealMoney'
    ];
    protected $casts =  [
        'attrData' => 'array',
        'materialData' => 'array',
        'setMealData' => 'array'
    ];

    public function  getMD5()
    {
        ksort($this->attrData);
        $str = $this->userId . $this->uniacid . $this->storeId . $this->spuId . 'checkout' . $this->specMd5 . json_encode($this->attrData ?? [], 320) . json_encode($this->setMealData ?? [], 320);
        return md5($str);
    }

    public function getSpuAttribute()
    {
        if (!$this->_spu) {
            $this->_spu = StoreGoodsBase::where('uniacid', $this->uniacid)
                ->where('storeId', $this->storeId)->where("spuId", $this->spuId)->first();
        }

        return $this->_spu;
    }

    public function getSkuAttribute()
    {
        if (!$this->_sku) {
            $this->_sku = StoreGoodsSkuBase::where('uniacid', $this->uniacid)
                ->where('storeId', $this->storeId)
                ->where("spuId", $this->spuId)
                ->where('specMd5', $this->specMd5)
                ->first();
        }

        return $this->_sku;
    }

    public function check()
    {
        $spu = $this->spu;
        if (empty($spu)) {
            throw new BadRequestException('当前商品不存在或已下架');
        }
        $sku = $this->sku;
        if (empty($sku) || empty($sku->sku)) {
            throw new BadRequestException('当前SKU不存在或已下架');
        }
        if ($sku->surplusInventory  <= 0) {
            throw new BadRequestException($this->spu->spu->name . '已售罄');
        }
        if ($sku->price != $this->price) {
            throw new BadRequestException('当前商品价格发生改变，请重新添加');
        }
        if ($this->num > $sku->surplusInventory) {
            throw new BadRequestException($spu->name . '已售罄');
        }
        if ($this->num > 0) {
            if ($spu->spu->orderlimitSwitch == 1 && $spu->spu->orderlimit < $this->num) {
                throw new BadRequestException("每单最大购买数量：" . $spu->spu->orderlimit);
            }

            if ($spu->spu->userlimitSwitch == 1 && $spu->spu->userlimit) {
                $userBuy=OrderGoods::where('spuId',$spu->spu->id)->where('userId',$this->userId)->count();
                if($spu->spu->userlimit < ($userBuy + $this->num)){
                    throw new BadRequestException("购买数量已达到上限");
                }

            }

            if ($spu->spu->daylimitSwitch == 1 && $spu->spu->daylimit ) {
                $today = Carbon::today();
                $userDayBuy=OrderGoods::whereDate('created_at', $today)->where('spuId',$spu->spu->id)->where('userId',$this->userId)->count();
                if($spu->spu->daylimit < ($userDayBuy + $this->num)){
                    throw new BadRequestException("今天购买数量已达到上限");
                }
            }
        }
        return true;
    }

    public function model($inCart = true,$specMd5='',$setMealData='',$attrData='',$diningType='')
    {
        $specMd5=$specMd5?:$this->getMD5();
        if ($inCart) {
            $goods = Cart::where('specMd5', $specMd5)
                ->where("uniacid", $this->uniacid)
                ->where("storeId", $this->storeId)
                ->where("userId", $this->userId);
            if($setMealData){
                $goods->where("setMealData",'like', '%'.stripslashes(json_encode($setMealData,JSON_UNESCAPED_UNICODE).'%'));
            }
            if($attrData){
                $goods->where("attrData",'like', '%'.stripslashes(json_encode($attrData,JSON_UNESCAPED_UNICODE).'%'));

            }
            if($diningType){
                $goods->where("diningType",$diningType);
            }
            $goods=$goods->first();
        }
        $spu = $this->spu;
        $sku = $this->sku;
        if (empty($goods)) {
            $goods = new Cart();
            $goods->fill($this->toArray());
            $goods->uniacid = $this->uniacid;
            $goods->MD5 = $this->getMD5();
            $goods->price = $sku->price;
            $goods->spuId = $this->spuId;
            $goods->specMd5 = $this->specMd5;
            $goods->storeId = $this->storeId;
            $goods->userId = $this->userId;
            $goods->discountType = $this->discountType ?? 0;
            $goods->discountNum = $this->discountNum ?? 0;
            $goods->discountMoney = 0;
            $goods->materialMoney = 0;
            $goods->sellMoney = 0;
            $goods->diningType = $diningType;
            $goods->boxPrice = $sku->sku->boxMoney ?? 0;
            $goods->discountPrice = $this->discountPrice ?? 0;
            $goods->setMealMoney = $this->setMealMoney ?? 0;
        }

        $goods->num = (intval($goods->num) + intval($this->num)) < 0 ? 0 : intval($goods->num) + intval($this->num);
        if (intval($this->num) > 0 && $goods->num < $spu->spu->min) {
            $goods->num = $spu->spu->min;
        }
        if (intval($this->num) < 0 && $goods->num < $spu->spu->min) {
            $goods->num = 0;
            $goods->discountNum = 0;
        }

        if (in_array($goods->discountType, [0, 6, 7, 8, 10])) {
            $goods->getDiscount();
        }
        $goods->setMealMoney = $goods->getSetMealMoney();
        $goods->materialMoney = $goods->getMaterialMoney();
        $goods->discountMoney = $goods->getDiscountMoney();
        $goods->sellMoney = $goods->getSellMoney();
        if($this->num==-1&&$goods->discountType==9){
            $goods->money = $goods->getSellMoney();
        }else{
            $goods->money = $goods->getMoney();
        }

        $goods->boxMoney = $goods->getBoxMoney();
        return $goods;
    }

    public function goods()
    {
        return $this->hasOne(GoodsSpuBase::class, 'id', 'spuId');
    }

    public function getGoodsMoney()
    {
        return bcsub(bcmul($this->price, intval($this->num), 2), $this->getDiscountMoney(), 2);
    }

    public function  getMaterialMoney()
    {
        $money = 0.00;
        if ($this->attrData['material']) {
            foreach ($this->attrData['material'] as $key => $item) {
                $material = Material::where('uniacid', $this->uniacid)->find($item['id']);
                if($material){
                    $money = bcadd($money, bcmul($material->price, intval($item['num']), 2), 2);
                }
//                if (empty($material)) {
//                    throw new BadRequestException($item['name'] . '不存在或已下架');
//                }
//                if ($material->inventory < intval($item['num'])) {
//                    throw new BadRequestException($item['name'] . "库存不足");
//                }
//                $money = bcadd($money, bcmul($material->price, intval($item['num']), 2), 2);
            }
        }
        return bcmul($money, $this->num, 2);
    }


    public function  getDiscountMoney()
    {
        return bcmul(bcsub($this->price, $this->discountPrice, 2), intval($this->discountNum), 2);
    }

    public function  getSellMoney()
    {
        return bcadd(bcadd($this->materialMoney, bcmul($this->price, intval($this->num), 2), 2), $this->setMealMoney, 2);
    }

    public function  getMoney()
    {
        return bcsub($this->getSellMoney(), $this->getDiscountMoney(), 2);
    }

    public function  getBoxMoney()
    {
        return bcmul($this->boxPrice, $this->num, 2);
    }

    public function getDiscount()
    {
        $sku = $this->sku;
        if ($sku->discount) {
            if ($sku->discount['type'] == 6) {
                $this->discountType = 6;
                $this->discountLabel = $sku->discount['discountLabel'];
                $this->discountPrice = $sku->discount['price'];
                $this->discountNum = $this->num;
            } else if ($sku->discount['type'] == 10) {
                $this->discountType = 10;
                $this->discountLabel = $sku->discount['discountLabel'];
                $this->discountPrice = $sku->discount['price'];
                $this->discountNum = $this->num;
                $this->equityCardMemberId = $sku->discount['equityCardMemberId'] ?? 0;
            } else {
                $goodsNumFirst = DB::table('takeout_cart')->select(
                    DB::raw('IFNULL(sum(num),0) as num,IFNULL(sum(discountNum),0) as discountNum')
                )->where("userId", $this->userId)
                    ->where('uniacid', $this->uniacid)
                    ->where('storeId', $this->storeId)
                    ->where('spuId', $this->spuId)
                    ->where('specMd5', $this->specMd5)
                    ->where('id', '!=', $this->id ?? 0)
                    ->first();
                $num = $sku->discount['discountRule']['discountType'] == 1 ? 1 : $sku->discount['discountRule']['discountNum'];
                if ($sku->discount['discountRule']['moneyType'] == 1) {
                    $price = bcmul(bcdiv($this->price, 100, 4), $sku->discount['discountRule']['discount'], 2);
                } elseif ($sku->discount['discountRule']['moneyType'] == 2) {
                    $price = bcsub($this->price, $sku->discount['discountRule']['discount'], 2);
                    $price = $price < 0 ? 0 : $price;
                } elseif ($sku->discount['discountRule']['moneyType'] == 3&&$this->num==$sku->discount['discountRule']['full']) {
                    $price = $sku->discount['discountRule']['discount'];
                }
                $goodsNum = bcdiv(($this->num + $goodsNumFirst->num), $sku->discount['discountRule']['full']);
                $discountNum = $goodsNum > $num ? $num : $goodsNum;
                $discountNum = $discountNum > $goodsNumFirst->discountNum ? ($discountNum - $goodsNumFirst->discountNum) : 0;
                if ($discountNum >= 1) {
                    $this->discountType = $sku->discount['type'];
                    $this->discountLabel = $sku->discount['discountLabel'];
                    $this->discountPrice = $price;
                    $this->discountNum = $goodsNum > $num ? $num : $goodsNum;
                } else {
                    $this->discountType = 0;
                    $this->discountLabel = null;
                    $this->discountPrice = 0;
                    $this->discountNum = 0;
                }
            }
        } else {
            $this->discountType = 0;
            $this->discountLabel = null;
            $this->discountPrice = 0;
            $this->discountNum = 0;
        }
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            try {
                if ($model->num > 0) {
                    $model->check();
                }
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        });

        static::deleted(function ($model) {
            $list = Cart::where('storeId', $model->storeId)
                ->where('spuId', $model->spuId)
                ->where('specMd5', $model->specMd5)
                ->get();
            collect($list)->each(function ($goods) {
                $model = new Cart();
                $model->fill($goods->toArray());
                $model->uniacid = $goods->uniacid;
                $model->storeId = $goods->storeId;
                $model->userId = $goods->userId;
                $model->num = 0;
                $model = $model->model();
                DB::table('takeout_cart')->where('id', $model->id)->update($model->toArray());
            });
        });
    }

    public function  getSetMealMoney()
    {
        $money = 0.00;
        if ($this->setMealData) {
            foreach ($this->setMealData as $key => $item) {
                $money = bcmul(collect($this->setMealData)->sum('money'), 1, 2);
            }
        }
        return bcmul($money, $this->num, 2);
    }


    public function getSetMealDataAttribute()
    {
        if ($this->attributes['setMealData']) {
            return collect(json_decode($this->attributes['setMealData'], 320))->map(function ($goods, $key) {
                $goods['money'] = bcmul($goods['price'], intval($goods['num']), 2);
                if ($goods['attrData']['material']) {
                    foreach ($goods['attrData']['material'] as $key => $item) {
                        $material = Material::where('uniacid', $this->uniacid)->find($item['id']);
                        if (empty($material)) {
                            throw new BadRequestException($item['name'] . '不存在或已下架');
                        }
                        if ($material->inventory < intval($item['num'])) {
                            throw new BadRequestException($item['name'] . "库存不足");
                        }
                        $goods['money'] = bcadd($goods['money'], bcmul($material->price, intval($item['num']), 2), 2);
                    }
                }
                return $goods;
            })->values();
        }
        return $this->attributes['setMealData'];
    }

    public function category()
    {
        return $this->hasMany(SpuCatgorys::class, 'spuId', 'spuId');
    }
}
