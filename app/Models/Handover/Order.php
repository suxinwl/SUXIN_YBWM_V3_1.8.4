<?php

namespace App\Models\Handover;
use App\Enums\PayEnum;
use App\Models\BaseModel;
use App\Models\InStore\Order\Order as OrderOrder;
use App\Models\Order\TakeOutOrder;
use App\Models\PersionPayOrder;
use App\Models\StoredValueOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends BaseModel
{
    protected $table = 'statistics_order';
    use HasFactory;
    public $_subOrder;
    protected $appends = [
        'typeFormat',
        'payTypeFormat',
        'scoreFormat',
        'subOrder'
    ];


    public function getSubOrderAttribute()
    {
        if (empty($this->_subOrder)) {
            switch ($this->type) {
                case 1:
                    $this->_subOrder = TakeOutOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 2:
                    $this->_subOrder = StoredValueOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 3:
                    $this->_subOrder = PersionPayOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 4:
                    $this->_subOrder = OrderOrder::where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
            }
        }
        return $this->_subOrder;
    }


    public function getTypeFormatAttribute()
    {
        if ($this->type == 1) {
            if ($this->scene == 1) {
                return "外卖订单";
            } elseif ($this->scene == 2) {
                return  "自提订单";
            }
        } else {
            $data = [
                2 => "储值订单",
                3 => "买单订单",
                4 => "店内订单"
            ];
            return $data[$this->type];
        }
    }
    public function getPayTypeFormatAttribute()
    {
        return PayEnum::format($this->payType);
    }
    public function getScoreFormatAttribute()
    {
        return appTypeFormat($this->score);
    }
}
