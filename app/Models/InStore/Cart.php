<?php

namespace App\Models\InStore;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSpu;
use App\Models\Material;
use App\Models\SpuCatgorys;
use App\Models\Store\GoodsList;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsBase;
use App\Models\Store\StoreGoodsSku;
use App\Models\Store\StoreGoodsSkuBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Cart extends BaseModel
{
    protected $table = 'instore_cart';
    use HasFactory;
    public $_spu;
    public $_sku;
    protected $fillable = [
        'equityCardMemberId', 'spuId', 'specMd5', 'discountPrice', 'attrData', 'materialData', 'goodsType', 'tableId', 'diningType', 'score', 'adminId', 'setMealData', 'setMealMoney'
    ];
    protected $casts =  [
        'attrData' => 'array',
        'materialData' => 'array',
        'setMealData' => 'array'
    ];

    protected $attributes = [
        'scene' => SceneEnum::SCENE_EATIN
    ];

    public function  getMD5()
    {
        ksort($this->attrData);
        if ($this->diningType == 4) {
            $str = intval($this->score) . intval($this->uniacid) . intval($this->storeId) . 'inStore' . intval($this->tableId) . $this->specMd5 . json_encode($this->attrData ?? [], 320). json_encode($this->setMealData ?? [], 320);
        } else {
            $str = intval($this->score) . intval($this->userId) . intval($this->uniacid) . intval($this->storeId) . intval($this->tableId) . intval($this->tableId) . 'inStore' . $this->specMd5 . json_encode($this->attrData ?? [], 320). json_encode($this->setMealData ?? [], 320);
        }
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
        $sku = $this->sku;
        if (empty($spu)) {
            throw new BadRequestException('当前商品不存在或已下架');
        }
        if (empty($sku) || empty($sku->sku)) {
            throw new BadRequestException('当前SKU不存在或已下架');
        }
        if ($sku->surplusInventory  <= 0) {
            throw new BadRequestException($this->spu->spu->name . '已售罄');
        }
        $price = $sku->selfPriceSwitch == 1 ? $sku->inStorePrice : $sku->price;
        if ($price != $this->price) {
            throw new BadRequestException('当前商品价格发生改变，请重新添加');
        }
        if ($this->num > $sku->surplusInventory) {
            throw new BadRequestException($spu->name . '已售罄');
        }
        if ($this->num > 0 && $this->diningType != 4) {
            if ($spu->spu->orderlimitSwitch == 1 && $spu->spu->orderlimit < $this->num) {
                throw new BadRequestException("每单最大购买数量：" . $spu->spu->orderlimit);
            }
            $userLimitKey = "userGoods:" . $spu->spu->storeId . $spu->spu->id . ":" . $this->userId;
            $userBuy = Cache::get($userLimitKey) ?? 0;
            if ($spu->spu->userlimitSwitch == 1 && $spu->spu->userlimit < ($userBuy + $this->num)) {
                throw new BadRequestException("购买数量已达到上限");
            }
            $userDayLimitKey = "userGoods:" . $spu->spu->storeId . date("Y-m-d") . ":" . $spu->spu->id . ":" . $this->userId;
            $userDayBuy = Cache::get($userDayLimitKey) ?? 0;
            if ($spu->spu->daylimitSwitch == 1 && $spu->spu->daylimit < ($userDayBuy + $this->num)) {
                throw new BadRequestException("今天购买数量已达到上限");
            }
        }
        return true;
    }

    public function  model($inCart = true,$tableId='',$specMd5='',$setMealData='',$attrData='')
    {
        $specMd5=$specMd5?:$this->getMD5();
        if ($inCart) {
            $goods = Cart::where('specMd5', $specMd5)
                ->where("uniacid", $this->uniacid)
                ->where("storeId", $this->storeId)
                ->where("userId", $this->userId)->where("diningType", $this->diningType);
            if($tableId){
                $goods->where("tableId",$tableId);
            }
            if($setMealData){
                $goods->where("setMealData",'like', '%'.stripslashes(json_encode($setMealData,JSON_UNESCAPED_UNICODE).'%'));
            }
            if($attrData){
                $goods->where("attrData",'like', '%'.stripslashes(json_encode($attrData,JSON_UNESCAPED_UNICODE).'%'));

            }
            $goods=$goods->first();
        }
        $sku = $this->sku;
        $spu = $this->spu;
        if (empty($goods)) {
            $goods = new Cart();
            $goods->tableId = $this->tableId ?? 0;
            $goods->fill($this->toArray());
            $goods->uniacid = $this->uniacid;
            $goods->MD5 = $this->getMD5();
            $goods->price = $sku->selfPriceSwitch == 1 ? $sku->inStorePrice : $sku->price;
            $goods->spuId = $this->spuId;
            $goods->specMd5 = $this->specMd5;
            $goods->storeId = $this->storeId;
            $goods->userId = $this->userId;
            $goods->score = $this->score;
            $goods->discountType = 0;
            $goods->discountNum = 0;
            $goods->discountMoney = 0;
            $goods->materialMoney = 0;
            $goods->sellMoney = 0;
            $goods->boxPrice = $sku->sku->boxMoney;
            $goods->discountPrice = 0;
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
        $goods->money = $goods->getMoney();
        $goods->boxMoney = $goods->getBoxMoney();
        return $goods;
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
            } elseif ($sku->discount['type'] == 10) {
                $this->discountType = 10;
                $this->discountLabel = $sku->discount['discountLabel'];
                $this->discountPrice = $sku->discount['price'];
                $this->discountNum = $this->num;
                $this->equityCardMemberId = $sku->discount['equityCardMemberId'] ?? 0;
            } else {
                $num = $sku->discount['discountRule']['discountType'] == 1 ? 1 : $sku->discount['discountRule']['discountNum'];
                if ($sku->discount['discountRule']['moneyType'] == 1) {
                    $price = bcmul(bcdiv($this->price, 100, 4), $sku->discount['discountRule']['discount'], 2);
                } elseif ($sku->discount['discountRule']['moneyType'] == 2) {
                    $price = bcsub($this->price, $sku->discount['discountRule']['discount'], 2);
                    $price = $price < 0 ? 0 : $price;
                } elseif ($sku->discount['discountRule']['moneyType'] == 3) {
                    $price = $sku->discount['discountRule']['discount'];
                } else {
                    $price = $this->price;
                }
                $goodsNum = bcdiv($this->num, $sku->discount['discountRule']['full']);
                $discountNum = $goodsNum > $num ? $num : $goodsNum;
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

    public function goods()
    {
        return $this->hasOne(SpuList::class, 'id', 'spuId');
    }

    public function getGoodsMoney()
    {
        return bcsub(bcmul($this->price, intval($this->num)), $this->getDiscountMoney(), 2);
    }

    public function  getMaterialMoney()
    {
        $money = 0.00;
        if ($this->attrData['material']) {
            foreach ($this->attrData['material'] as $key => $item) {
                $material = Material::where('uniacid', $this->uniacid)->find($item['id']);
                if (empty($material)) {
                    throw new BadRequestException($item['name'] . '不存在或已下架');
                }
                if ($material->inventory < intval($item['num'])) {
                    throw new BadRequestException($item['name'] . "库存不足");
                }
                $money = bcadd($money, bcmul($material->price, intval($item['num']), 2), 2);
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
    }

    public function category()
    {
        return $this->hasMany(SpuCatgorys::class, 'spuId', 'spuId');
    }
}
