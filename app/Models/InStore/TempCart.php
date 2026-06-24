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
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TempCart extends BaseModel
{
    protected $table = 'instore_cart';
    use HasFactory;
    public $_spu;
    public $_sku;
    protected $fillable = [
        'diningType',
        'notes',
        'boxMoney',
        'reason',
        'logo',
        'name',
        'isTemp',
        'tempIndex',
        'uniacid',
        'storeId',
        'price',
        'num',
        'discountType',
        'score',
        'adminId',
        'tableId',
        'money',
        'sellMoney',
        'boxMoney',
    ];

    protected $casts =  [
        'attrData' => 'array',
        'materialData' => 'array',
        'setMealData' => 'array'
    ];

    protected $attributes = [
        'scene' => SceneEnum::SCENE_EATIN,
        'discountType' => 0,
        'discountNum' => 0,
        'discountMoney' => 0
    ];

    public function  getMD5()
    {
        if ($this->diningType == 4) {
            if ($this->tempIndex == 0) {
                $count = DB::table('instore_cart')
                    ->where('uniacid', $this->uniacid)
                    ->where('storeId', $this->storeId)
                    ->where('discountType', $this->discountType)
                    ->where('score', $this->score)
                    ->where('tableId', $this->tableId)
                    ->count();
                $this->tempIndex = intval($count) + 1;
            }
            $str =  $this->discountType . intval($this->score) . intval($this->uniacid) . intval($this->storeId) . 'inStore' . intval($this->tableId) . $this->tempIndex;
        } else {
            if ($this->tempIndex == 0) {
                $count = DB::table('instore_cart')
                    ->where('uniacid', $this->uniacid)
                    ->where('storeId', $this->storeId)
                    ->where('discountType', $this->discountType)
                    ->where('score', $this->score)
                    ->where('tableId', $this->tableId)
                    ->where('adminId', $this->adminId)
                    ->count();
                $this->tempIndex = intval($count) + 1;
            }
            $str = intval($this->score) . intval($this->adminId) . intval($this->uniacid) . intval($this->storeId) . intval($this->tableId) . 'inStore' . $this->tempIndex;
        }
        return md5($str);
    }


    public function  model($inCart = true, $disCountChange = true)
    {
        if ($inCart) {
            $goods = TempCart::where('MD5', $this->getMD5())
                ->where("uniacid", $this->uniacid)
                ->where("storeId", $this->storeId)
                ->where("uniacid", $this->uniacid)
                ->where("diningType", $this->diningType)
                ->first();
        }
        if (empty($goods)) {
            $goods = new TempCart();
            $goods->tableId = $this->tableId ?? 0;
            $goods->fill($this->toArray());
            $goods->uniacid = $this->uniacid;
            $goods->MD5 = $this->getMD5();
            $goods->price = $this->price;
            $goods->spuId = 0;
            $goods->specMd5 = 'tempCart';
            $goods->storeId = $this->storeId;
            $goods->userId = $this->userId;
            $goods->score = $this->score;
            $goods->materialMoney = 0;
            $goods->sellMoney = 0;
            $goods->boxPrice = $this->boxMoney ?? 0;
            $goods->num = 0;
        }
        $goods->num = (intval($goods->num) + intval($this->num)) < 0 ? 0 : intval($goods->num) + intval($this->num);
        $goods->setMealMoney = $goods->getSetMealMoney();
        $goods->materialMoney = $goods->getMaterialMoney();
        $goods->discountMoney = $goods->getDiscountMoney();
        $goods->sellMoney = $goods->getSellMoney();
        $goods->money = $goods->getMoney();
        $goods->boxMoney = $goods->getBoxMoney();
        return $goods;
    }

    public function getGoodsMoney()
    {
        return bcsub(bcmul($this->price, intval($this->num)), $this->getDiscountMoney(), 2);
    }

    public function  getMaterialMoney()
    {
        return 0;
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

        return 0;
    }


    public function getSetMealDataAttribute()
    {
        return 0;
    }

}
