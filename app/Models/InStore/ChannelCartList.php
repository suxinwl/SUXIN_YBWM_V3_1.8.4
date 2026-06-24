<?php

namespace App\Models\InStore;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\Material;
use App\Models\Member;
use App\Models\Store;
use App\Models\Store\StoreGoods;
use App\Models\Store\StoreGoodsSku;
use App\Models\Tables\Servers;
use App\Models\Tables\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class ChannelCartList extends BaseModel
{
    public $_diningType;
    public $_money;
    public $_lineMoney;
    public $_discounts = [];
    public $_newSub;
    public $_discountMoney;
    public $_goodsMoney;
    public $_deliveryMoney;
    public $_vipFreeMail;
    public $_config;
    public $_goodsList;
    public $_tableFormat;
    public $_vipDiscount;
    public $_tableNum;
    public $_tableMoney;
    protected $fillable = [
        'uniacid', 'storeId', 'userId', 'scene', 'lat', 'lng', 'tableId', 'diningType', 'score', 'adminId', 'appType'
    ];

    protected $appends = [
        'money',
        'goodsSellMoney',
        'categoryIds',
        'boxMoney',
        'discountMoney',
        'goodsCount',
        'fullsub',
        'lineMoney',
        'newSub',
        'discounts',
        'goodsList',
        'tableMoney',
        'tableFormat',
        'tableNum'
    ];

    protected $hidden = [
        'store'
    ];

    /**
     * Undocumented function
     *商品列表
     * @return void
     */

    public function getConfigAttribute()
    {
        if (!$this->_config) {
            $this->_config = $this->store->inStoreSetting ?? null;
        }
        return $this->_config;
    }

    public function getGoodsListAttribute()
    {
        if (!$this->_goodsList) {
            $userId = $this->userId;
            $adminId = $this->adminId;
            $this->_goodsList = ChannelCart::where('uniacid', $this->uniacid)
                ->where('diningType', $this->diningType)
                ->where('tableId', $this->tableId)
                ->where('score', $this->score)
                ->where("storeId", $this->storeId)
                ->when(!in_array($this->score, [10, 11]) && $this->diningType != 4, function ($q) use ($userId) {
                    return $q->where('userId', $userId);
                })
                ->when(in_array($this->score, [10, 11]) && $this->diningType != 4, function ($q) use ($adminId) {
                    return $q->where('adminId', $adminId);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return $this->_goodsList;
    }

    /**
     * Undocumented function
     *原价总金额
     * @return void
     */
    public function getSellMoneyAttribute()
    {
        return bcadd(collect($this->goodsList)->sum('sellMoney'), $this->boxMoney, 2);
    }

    public function getGoodsSellMoneyAttribute()
    {
        return  bcmul(collect($this->goodsList)->sum('sellMoney'), 1, 2);
    }

    public function getGoodsMoneyAttribute()
    {
        if (empty($this->_goodsMoney)) {
            $goodsMoney = bcmul(collect($this->goodsList)->sum('money'), 1, 2);
            $fullSub = $this->fullsub['sub'] ?? 0;
            $newSub = $this->newSub ?? 0;
            $goodsMoney = bcsub($goodsMoney, $fullSub, 2);
            $goodsMoney = bcsub($goodsMoney, $newSub, 2);
            $goodsMoney  = $goodsMoney < 0 ? 0 : $goodsMoney;
            $this->_goodsMoney =  $goodsMoney;
        }
        return $this->_goodsMoney;
    }

    /**
     * Undocumented function
     *总金额   = 商品总金额 + 总包装费 + 配送费
     * @return void
     */
    public function getMoneyAttribute()
    {
        if (empty($this->_money)) {
            $this->_money = bcadd($this->goodsMoney, $this->boxMoney, 2);
        }
        return $this->_money;
    }

    /**
     * Undocumented function
     *总金额   = 商品总金额 + 总包装费
     * @return void
     */
    public function getLineMoneyAttribute()
    {
        if (empty($this->_lineMoney)) {
            $this->_lineMoney = $this->sellMoney;
        }
        return $this->_lineMoney;
    }


    /**
     * Undocumented function
     *总包装费
     * @return void
     */
    public function getBoxMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->where('pack', 1)->sum('boxMoney'), 1, 2);
    }

    public function getBfGoodsMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->sum('money'), 1, 2);
    }

    /**
     * Undocumented function
     *加料总金额
     * @return void
     */
    public function getMaterialMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->sum('materialMoney'), 1, 2);
    }


    /**
     * Undocumented function
     *商品数量
     * @return void
     */
    public function getGoodsCountAttribute()
    {
        return collect($this->goodsList)->sum('num');
    }

    public function getSceneAttribute()
    {
        return SceneEnum::SCENE_EATIN;
    }

    public function getFullSubAttribute()
    {
        return null;
    }

    public function getNewSubAttribute()
    {
        return null;
    }

    public function getDiscountMoneyAttribute()
    {
        if (empty($this->_discountMoney)) {
            $this->_discountMoney = bcadd(collect($this->discounts)->sum('money'), $this->goodsDiscountMoney, 2);
        }
        return $this->_discountMoney;
    }
    public function getGoodsDiscountMoneyAttribute()
    {
        return bcmul(collect($this->goodsList)->sum('discountMoney'), 1, 2);
    }

    public function getVipFreeMailAttribute()
    {
        return $this->_vipFreeMail;
    }

    public function getDiscountsAttribute()
    {
        return [];
    }

    public function getTableMoneyAttribute()
    {
        if (!$this->_tableMoney && $this->diningType == 4 && !$this->tables->orderSn) {
            $tableId = $this->tableId;
            $server = Servers::where('uniacid', $this->uniacid)
                ->where('storeId', $this->storeId)
                ->whereHas('tables', function ($q) use ($tableId) {
                    return $q->where('tableId', $tableId);
                })->first();
            if ($server) {
                $money = $server->type == 1 ? bcmul(intval($this->tables->people), floatval($server->price), 2) : floatval($server->price);
            }
            $this->_tableMoney = $money ?? 0;
            $this->_tableFormat = $server->name ?? null;
            $this->_tableNum = $server->type == 1 ?  $this->tables->people : 1;
        }
        return $this->_tableMoney ?? 0;
    }
    public function getTableFormatAttribute()
    {
        return $this->_tableFormat ?? null;
    }

    public function getTableNumAttribute()
    {
        return $this->_tableNum ?? 0;
    }
    public function tables()
    {
        return $this->hasOne(Table::class, 'id', 'tableId');
    }
}
