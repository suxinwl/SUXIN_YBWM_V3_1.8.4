<?php

namespace App\Models\TablesReserve;

use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Member\Address;
use App\Models\PayConfig;
use App\Models\Store;
use App\Models\Tables\Type;
use App\Services\ConfigService;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Checkout extends BaseModel
{
    public $_order;
    use HasFactory;

    protected $appends = [
        'money', 'store'
    ];
    protected $fillable = [
        'uniacid', 'storeId', 'typeId', 'areaId', 'appointmentTime', 'userId', 'notes', 'score', 'num', 'person', 'mobile', 'contact'
    ];

    public function type()
    {
        return $this->hasOne(Type::class, 'id', 'typeId');
    }
    public function getStoreAttribute()
    {
        return DB::table('store')->select(['id', 'name'])->where('id', $this->storeId)->first();
    }

    public function getConfigAttribute()
    {
        return ConfigService::getStoreConfig('bookTable', $this->storeId);
    }

    public function getMoneyAttribute()
    {
        return bcmul($this->type->earnest ?? 0, $this->num);
    }

    public function getAutoReceiveAttribute()
    {
        return $this->config['switch'] == 1 && $this->config['receive'] == 1 ? 1 : 0;
    }

    public function getOrderAttribute()
    {
        return  new Order([
            'userId' => $this->userId,
            'uniacid' => $this->uniacid,
            'storeId' => $this->storeId,
            'typeId' => $this->typeId,
            'areaId' => intval($this->areaId),
            'notes' => $this->notes ?? null,
            'orderSn' => getTakeOutNo(),
            'money' => $this->money,
            'sellMoney' => $this->money,
            'person' => $this->person,
            'mobile' => $this->mobile,
            'contact' => $this->contact,
            'score' => $this->score,
            'autoReceive' => $this->autoReceive,
            'appointmentTime' => $this->appointmentTime,
        ]);
    }


    public function getPayAttribute()
    {
        $channel = $this->channel;
        $score = $this->score;
        $storeId = $this->storeId;
        $list = PayConfig::where(function ($q) use ($channel, $score) {
            if (in_array($score, [3, 12])) {
                return $q->whereIn('payType', ["alipay", "balance"]);
            } elseif (in_array($score, [1, 2])) {
                return $q->whereIn('payType', ["weixin", "balance"]);
            } else {
                return $q->where('channel', $channel);
            }
        })
            ->when($this->money == 0, function ($q) {
                return $q->where('payType', 'balance');
            })
            ->when($this->store->payChange == 0, function ($q) {
                return $q->where('storeId', 0);
            })
            ->when($this->store->payChange == 1, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->where('uniacid', $this->uniacid)
            ->where('state', 1)
            ->where('storeId', 0)
            ->get();
        foreach ($list as $key => $v) {
            $isDefault = $v->isDefault;
            $v->setAppends([]);
            if ($v->payType == 'balance') {
                $v->balance = $this->user->account->balance;
            }
            $list[$key] = $v;
        }
        if ($isDefault == 0) {
            $list = collect($list)->map(function ($item, $key) {
                $item = $item->toArray();
                if ($key == 0) {
                    $item['isDefault'] = 1;
                }
                return $item;
            });
        }
        return [
            'expiredTime' => '',
            'expirationMinute' =>  '',
            'orderId' => '',
            'orderType' => '',
            'orderSn' => $this->order->orderSn,
            'payList' => $list,
            'money' => $this->order->money
        ];
    }
}
