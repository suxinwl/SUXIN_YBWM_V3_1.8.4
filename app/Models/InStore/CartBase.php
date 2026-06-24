<?php

namespace App\Models\InStore;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\Goods\SpuList;
use App\Models\GoodsSearch\GoodsSpuBase;
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

class CartBase extends BaseModel
{
    protected $table = 'instore_cart';
    use HasFactory;
    public $_spu;
    public $_sku;
    protected $casts =  [
        'attrData' => 'array',
        'materialData' => 'array',
        'setMealData' => 'array'
    ];

    protected $attributes = [
        'scene' => SceneEnum::SCENE_EATIN
    ];
    protected $appends = [
        'goods'
    ];

    public function getGoodsAttribute()
    {
        $goods = DB::table('goods_spu')->select(['id', 'name', 'logo'])->where('id', $this->spuId)->first();
        if (!$goods) {
            return new GoodsSpuBase([
                'name' => $this->name,
                'logo' => null
            ]);
        }
        return $goods;
    }
}
