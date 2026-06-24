<?php

namespace App\Models;

use App\Models\InStore\Order\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CouponPack\Order as CouponPackOrder;
use App\Models\EquityCard\Order as EquityCardOrder;
use App\Models\PointsMall\Order as PointsMallOrder;
use App\Models\TablesReserve\Order as TablesReserveOrder;
use DB;

class PrinterLog extends BaseModel
{
    use HasFactory;
    protected $table = 'printer_log';
    protected $fillable = ['uniacid', 'storeId', 'printer_type ', 'sn', 'order_sn ', 'printer_order_id', 'content', 'respond_data', 'respond_msg'];
    protected $casts =  [];
    protected $_subOrder;
    protected $appends = [
        'originFormat'
    ];

    public function printer()
    {
        return $this->hasMany(Hardware::class, 'id', 'printer_id');
    }
    public function store()
    {
        return $this->hasMany(Store::class, 'id', 'storeId');
    }

    public function getSubOrderAttribute()
    {
        if (empty($this->_subOrder)) {
            switch ($this->origin) {
                case 1:
                    $this->_subOrder = DB::table('takeout_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 2:
                    $this->_subOrder = DB::table('storevalue_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 3:
                    $this->_subOrder = DB::table('persion_pay_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 4:
                    $this->_subOrder = DB::table('instore_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 5:
                    $this->_subOrder = DB::table('points_mall_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 6:
                    $this->_subOrder = DB::table('coupon_pack_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 7:
                    $this->_subOrder = DB::table('table_reserve_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
                case 8:
                    $this->_subOrder = DB::table('equity_card_order')->where('uniacid', $this->uniacid)->where('orderSn', $this->orderSn)->first();
                    break;
            }
        }
        return $this->_subOrder;
    }


    public static function  registerLog($printer, $order_sn, $printer_type, $content, $respond_data, $origin = 1, $description = '')
    {
        try {
            $data = json_decode($respond_data, true);
            switch ($printer_type) {
                case 1;
                    $sn = $printer['config']['feNum'];
                    $respond_msg = $data['msg'];
                    break;
                case 2;
                    $sn = $printer['config']['ylyNum'];
                    $respond_msg = $data['error_description'];
                    break;
                case 3;
                    $sn = $printer['config']['spySn'];
                    $respond_msg = $data['errormsg'];
                    break;
                case 4;
                    $sn = $printer['config']['daquSn'];
                    $respond_msg = $data['message'];
                    break;
                case 5;
                    $sn = $printer['config']['jiabodeviceID'];
                    $respond_msg = $data['msg'];
                    break;
                case 6;
                    $sn = $printer['config']['xinyeNo'];
                    $respond_msg = $data['msg'];
                    break;
                case 7;
                    $sn = $printer['config']['feNum'];
                    $respond_msg = $data['msg'];
                    break;
                case 8;
                    $sn = $printer['config']['xinyeNo'];
                    $respond_msg = $data['msg'];
                    break;
            }
            $printerLog = new PrinterLog();
            $printerLog->uniacid = $printer['uniacid'];
            $printerLog->printer_type = $printer_type;
            $printerLog->sn = $sn;
            $printerLog->order_sn  = $order_sn;
            $printerLog->content = $content;
            $printerLog->respond_data = $respond_data;
            $printerLog->respond_msg = $respond_msg ?: 'ok';
            $printerLog->storeId = $printer['storeId'];
            $printerLog->printer_id = $printer['id'];
            $printerLog->origin = $origin;
            $printerLog->printer_name = $printer['config']['name'];
            $printerLog->description = $description;
            $printerLog->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function getOriginFormatAttribute()
    {
        switch ($this->origin) {
            case 1:
                if ($this->subOrder->diningType == 0) {
                    return "外卖";
                } elseif ($this->subOrder->diningType == 1) {
                    return "打包带走";
                } elseif ($this->subOrder->diningType == 2) {
                    return "店内就餐";
                }
                break;
            case 2:
                return '充值';
                break;
            case 3:
                return '当面付';
                break;
            case 4:
                if ($this->subOrder->diningType == 4) {
                    return "扫码点餐";
                } elseif ($this->subOrder->diningType == 5) {
                    return "牌号送餐";
                } elseif ($this->subOrder->diningType == 6) {
                    return "叫号取餐";
                }
                break;
            case 5:
                return '积分商城';
                break;
            case 6:
                return '优惠券包';
                break;
            case 7:
                return '餐桌预定';
                break;
            case 8:
                return '权益卡';
                break;
        }
    }
}
