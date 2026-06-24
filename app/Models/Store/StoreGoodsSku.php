<?php

namespace App\Models\Store;

use App\Models\BaseModel;
use App\Models\EquityCard\Member;
use App\Models\GoodsActivity\Goods;
use App\Models\GoodsSearch\GoodsSkuBase;
use App\Models\GoodsSku;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class StoreGoodsSku extends BaseModel
{
    protected $table = 'store_goods_sku';
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $casts =  [
        'specName' => 'array',
    ];
    protected $appends = [
        'discount'
    ];

    public $_discount;

    public function sku()
    {
        return $this->hasOne(GoodsSku::class, 'specMd5', 'specMd5')->withTrashed();
    }

    public function getDiscountAttribute()
    {
        if (!$this->_discount) {
            if ($this->equityCard) {
                $this->_discount  = $this->equityCard;
                return $this->_discount;
            }
            $storeId = $this->storeId;
            $userId = auth('user')->user()->id ?? Request()->userId;
            $user = DB::table('member')->where('id', $userId)->where('mobile', '!=', '')->first();
            $diningType = Request()->diningType ?? 99;
            $discount = collect($this->spuDiscount)->filter(function ($item) use ($userId, $diningType) {
                if (in_array($diningType, [1, 2])) {
                    $scenario = 2;
                } elseif ($diningType == 0) {
                    $scenario = 1;
                } elseif (in_array($diningType, [5, 6])) {
                    $scenario = 4;
                } elseif ($diningType == 4) {
                    $scenario = 3;
                } else {
                    return $scenario = 0;
                }
                return in_array($scenario, $item->scenario ?? []);
            })->where('specMd5', $this->specMd5)
                ->where("startTime", "<=", Carbon::now()->toDateTimeString())
                ->where("endTime", ">=", Carbon::now()->toDateTimeString())
                ->first();
            if ($discount && in_array($this->discuontType, [0, 6, 7, 8])) {
                $price = $this->price;
                if ($discount->type == 6) {
                    if ($user) {
                        if ($discount->userType == 1) {
                            if (in_array($diningType, [0, 1, 2])) {
                                $discountValue = $discount->wmDiscount;
                            } elseif (in_array($diningType, [4, 5, 6])) {
                                $discountValue = $discount->dnDiscount;
                            }
                            if ($discount->discountType == 1) {
                                $price = bcmul(bcdiv($price, 100, 4), $discountValue * 10, 2);
                            }
                            if ($discount->discountType == 2) {
                                $price = bcsub($price, $discountValue, 2);
                                if ($price < 0) {
                                    $price = 0;
                                }
                            }
                            if ($discount->discountType == 3) {
                                $price = $discountValue;
                                if ($price < 0) {
                                    $price = 0;
                                }
                                $price = bcmul($price, 1, 2);
                            }
                            $discount = collect($discount)->toArray();
                            $discount['price'] = $price;
                            $discount['linePrice'] = $this->price;
                        } elseif ($discount->userType == 2) {
                            $rule = collect($discount->discountRule)->where('id', $user->vipId)->first();
                            if ($rule) {
                                if (in_array($diningType, [0, 1, 2])) {
                                    $discountValue = $rule['wmDiscount'];
                                } elseif (in_array($diningType, [4, 5, 6])) {
                                    $discountValue = $rule['dnDiscount'];
                                }
                                if ($discount->discountType == 1) {
                                    $price = bcmul(bcdiv($price, 100, 4), $discountValue * 10, 2);
                                }
                                if ($discount->discountType == 2) {
                                    $price = bcsub($price, $discountValue, 2);
                                    if ($price < 0) {
                                        $price = 0;
                                    }
                                }
                                if ($discount->discountType == 3) {
                                    $price = $discountValue;
                                    if ($price < 0) {
                                        $price = 0;
                                    }
                                }
                                $discount = collect($discount)->toArray();
                                $discount['price'] = $price;
                                $discount['linePrice'] = $this->price;
                            } else {

                                $rule=$discount->discountRule[0];
                                if (in_array($diningType, [0, 1, 2])) {
                                    $discountValue = $rule['wmDiscount'];
                                } elseif (in_array($diningType, [4, 5, 6])) {
                                    $discountValue = $rule['dnDiscount'];
                                }
                                if ($discount->discountType == 1) {
                                    $price = bcmul(bcdiv($price, 100, 4), $discountValue * 10, 2);
                                }
                                if ($discount->discountType == 2) {
                                    $price = bcsub($price, $discountValue, 2);
                                    if ($price < 0) {
                                        $price = 0;
                                    }
                                }
                                if ($discount->discountType == 3) {
                                    $price = $discountValue;
                                    if ($price < 0) {
                                        $price = 0;
                                    }
                                }
                                $discount = collect($discount)->toArray();
                                $discount['price'] = 0;
                                $discount['linePrice'] = $this->price;
                                $discount['vipPrice'] = $price;

                                //$discount = null;
                            }
                        }
                    } else {
                        $discount = null;
                    }
                } else {
                    $key = "goodsDiscount:{$discount->id}:{$userId}";
                    $dayKey = "goodsDiscount:{$discount->activityId}:{$userId}:" . Carbon::now()->toDateString();
                    $keyLimit = Cache::get($key, 0);
                    $dayLimit = Cache::get($dayKey, 0);
                    if (
                        ($discount->rule['userLimit']['switch'] == 1 && $discount->rule['userLimit']['value'] <= $keyLimit) ||
                        ($discount->rule['dayLimit']['switch'] == 1 && $discount->rule['dayLimit']['value'] <= $dayLimit)
                    ) {
                        $discount = null;
                    } else {
                        $discount = collect($discount)->toArray();
                    }
                }
                $this->_discount  = $discount;
            }
        }
        return $this->_discount;
    }

    // public function getPriceAttribute()
    // {
    //     $diningType = Request()->diningType ?? 0;
    //     if (in_array($diningType, [4, 5, 6]) && $this->selfPriceSwitch == 1) {
    //         return $this->attributes['inStorePrice'];
    //     } else {
    //         return $this->attributes['price'];
    //     }
    // }
    public function getSpuDiscountAttribute()
    {
        $storeId = $this->storeId;
        $key = "goods:$this->spuId:store:$this->storeId:discount";
        $tag = "goodsDiscount:$this->spuId";
        if (!Cache::tags($tag)->has($key)) {
            $discount = Goods::where('uniacid', $this->uniacid)
                ->where('spuId', $this->spuId)
                ->where('state', 1)
                ->where("startTime", "<=", Carbon::now()->toDateTimeString())
                ->where("endTime", ">=", Carbon::now()->toDateTimeString())
                ->where(function ($q) use ($storeId) {
                    return $q->whereDoesntHave('stores')->orWhere(function ($q) use ($storeId) {
                        return $q->whereHas('stores', function ($q) use ($storeId) {
                            return $q->where('storeId', $storeId);
                        });
                    });
                })
                ->orderBy('type', 'desc')
                ->get();
            Cache::tags($tag)->put($key, $discount, 3600);
            return $discount;
        } else {
            return Cache::tags($tag)->get($key);
        }
    }


    public function getPriceAttribute()
    {
        $diningType = Request()->diningType ?? 1;
        if ($this->selfPriceSwitch) {
            if (in_array($diningType, [0,1,2])) {
                return $this->attributes['price'];
            } elseif (in_array($diningType, [4, 5, 6])) {
                return $this->attributes['inStorePrice'];
            }
        } else {
            return $this->attributes['price'];
        }
    }

    public function getEquityCardAttribute()
    {
        $userId = auth('user')->user()->id ?? Request()->userId;
        $member = Member::where('userId', $userId)->where('endTime', '>', Carbon::now()->toDateTimeString())->first();
        $card = $member->equityCard;
        if ($card->discountSwitch == 1) {
            $price = $this->price;
            $discount['equityCardMemberId'] = $member->id;
            $diningType = Request()->diningType ?? 99;
            if ($card->goodsType == 1) {
                $discountValue = $card->discountRule;
                if ($card->discountType == 1) {
                    $price = bcmul(bcdiv($price, 100, 4), $discountValue * 10, 2);
                } elseif ($card->discountType == 2) {
                    $price = bcsub($price, $discountValue, 2);
                    if ($price <= 0) {
                        $price = '0.00';
                    }
                    $price = bcmul($price, 1, 2);
                } elseif ($card->discountType == 3) {
                    if ($price > $discountValue) {
                        $price = bcmul($discountValue, 1, 2);
                        if ($price <= 0) {
                            $price = '0.00';
                        }
                        $price = bcmul($price, 1, 2);
                    }
                }

                $discount['price'] = $price;
                $discount['type'] = 10;
                $discount['discountLabel'] = $card->name;
                $discount['linePrice'] = $this->price;
                return $discount;
            } else {
                $discount = collect($card->goods)->where('specMd5', $this->specMd5)->first();
                if (!$discount) {
                    return null;
                }
                if (in_array($diningType, [0, 1, 2])) {
                    $discountValue = $discount['wmDiscount'];
                } elseif (in_array($diningType, [4, 5, 6])) {
                    $discountValue = $discount['dnDiscount'];
                }
                if ($card->discountType == 1) {
                    $price = bcmul(bcdiv($price, 100, 4), $discountValue * 10, 2);
                }
                if ($card->discountType == 2) {
                    $price = bcsub($price, $discountValue, 2);
                    if ($price < 0) {
                        $price = 0;
                    }
                }
                if ($card->discountType == 3) {
                    $goods=$card->goods;
                    foreach ($goods as $v){
                        if($this->spuId==$v['spuId']){
                            $price = $v['dnDiscount'];
                        }
                    }

                    //$price = $discountValue;
                    if ($price < 0) {
                        $price = 0;
                    }
                    $price = bcmul($price, 1, 2);
                }
                $discount['price'] = $price;
                $discount['type'] = 10;
                $discount['discountLabel'] = $card->name;
                $discount['linePrice'] = $this->price;
                return $discount;
            }
        }
        return null;
    }
}
