<?php

namespace App\Exports;

use App\Models\BulkOrder;
use App\Models\Order\TakeOutOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings; //导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderDataExport implements FromArray, WithHeadings, WithColumnWidths
{
    private $params;
    public function __construct($params)
    {
        $this->params = $params;
    }
    public function array(): array
    {
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $user = $params['user'];
        $keyword = $params['keyword'];
        $userKeyword = $params['userKeyword'];
        $data =  TakeOutOrder::where("uniacid", $uniacid)
            ->when($params['scene'], function ($q) use ($params) {
                return $q->where('scene', $params['scene']);
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($keyword, function ($q) use ($keyword) {
                return $q->where(
                    function ($q) use ($keyword) {
                        return $q->where('orderSn', "like", "%$keyword%")
                            ->orWhere(FacadesDB::raw('CONCAT(pickFix, pickNo)'), "like", "%$keyword%");
                    }
                );
            })
            ->when($params['payType'], function ($q) use ($params) {
                return $q->whereHas('orderIndex', function ($q) use ($params) {
                    if ($params['payType'] == 'wexin') {
                        return $q->weixin();
                    }
                    if ($params['payType'] == 'ali') {
                        return $q->ali();
                    }
                    if ($params['payType'] == 'balance') {
                        return $q->balance();
                    }
                    return $q;
                });
            })
            ->when($params['timeType'], function ($q) use ($params,) {
                return $q->where($params['timeChannel'] ?? 'created_at', '>=', $params['timeArr']['startTime'])
                    ->where($params['timeChannel'] ?? 'created_at', '<=', $params['timeArr']['endTime']);
            })
            ->when($params['source'], function ($q) use ($params) {
                return $q->where('source', appType($params['source']));
            })
            ->when($params['appointment'], function ($q) use ($params) {
                if ($params['appointment'] == "instant") {
                    return $q->where('appointment', 0);
                }
                if ($params['appointment'] == "appointment") {
                    return $q->where('appointment', 1);
                }
            })
            ->when($userKeyword, function ($q) use ($userKeyword) {
                return $q->whereHas('user', function ($q) use ($userKeyword) {
                    return $q->where('mobile', "like", "%$userKeyword%")
                        ->orWhere('nickname', "like", "%$userKeyword%");
                });
            })->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->when($params['state'], function ($q) use ($params) {
                switch ($params['state']) {
                    case 'close':
                        return $q->close();
                        break;
                    case 'unpaid':
                        return $q->unpaid();
                        break;
                    case 'unReceived':
                        return $q->unReceived();
                        break;
                    case 'making':
                        return $q->making();
                        break;
                    case 'waiting':
                        return $q->waiting();
                        break;
                    case 'delivery':
                        return $q->delivery();
                        break;
                    case 'complete':
                        return $q->complete();
                        break;
                    case 'refundApply':
                        return $q->refundApply();
                        break;
                    case 'refund':
                        return $q->refund();
                        break;
                    case 'afterSale':
                        return $q->afterSale();
                        break;
                    case 'reject':
                        return $q->reject();
                        break;
                    case 'deliveryAbnormal':
                        return $q->deliveryAbnormal();
                        break;
                    default:
                        return $q;
                }
            })
            ->orderBy('id', 'desc')->get();
        $data = empty($data) ? array() : $data->toArray();
        if (empty($data)) {
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData = [];
        if ($params['state'] == 'afterSale') {
            foreach ($data as $k => $v) {
                $newData[] = array(
                    'pickNo' => $v['pickNo'],
                    'store' => $v['store']['name'],
                    'nickname' => $v['user']['nickname'],
                    'mobile' => $v['user']['mobile'],
                    'payTypeFormat' => $v['payTypeFormat'],
                    'goodsFormat' => $v['goodsFormat'],
                    'afterSaleTime' => $v['afterSaleTime'],
                    'refundNotes' => $v['refundNotes'],
                    'refundCause' => $v['refundCause'],
                    'money' => $v['money'],
                    'refundFormat' => $v['refundFormat'],
                );
            }
            return $newData;
        }
        foreach ($data as $k => $v) {
            $newData[] = array(
                'completionTime' => $v['completionTime'],
                'pickNo' => $v['pickNo'],
                'store' => $v['store']['name'],
                'nickname' => $v['user']['nickname'],
                'mobile' => $v['user']['mobile'],
                'sellMoney' => $v['sellMoney'],
                'discountMoney' => $v['discountMoney'],
                'money' => $v['money'],
                'boxMoney' => $v['boxMoney'],
                'payTypeFormat' => $v['payTypeFormat'],
                'goodsFormat' => $v['goodsFormat'],
                'payStateFormat' => $v['payStateFormat'],
                'created_at' => $v['created_at'],
                'stateFormat' => $v['stateFormat'],
                'sourceFormat' => $v['sourceFormat'],
                'diningTypeFormat' => $v['diningTypeFormat'],
                'appointment' => $v['appointment'] == 0 ? '否' : '是',
                'serverTime' => $v['serverTime'],
                'notes' => $v['notes'],
                'address' => $v['address'] ? $v['address']['contact'] . '-' . $v['address']['mobile'] . '-' . $v['address']['address'] . $v['address']['description'] : ''
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array
    {
        if ($this->params['state'] == 'afterSale') {
            return [
                '订单完成时间',
                '流水号',
                '所属门店',
                '下单人信息',
                '手机号码',
                '支付方式',
                '商品信息',
                '申请时间',
                '申请方',
                '退款原因',
                '退款总额(元)',
                '退款状态',
                '退款时间',
            ];
        } else {
            return [
                '订单完成时间',
                '流水号',
                '所属门店',
                '下单人信息',
                '手机号码',
                '应付金额(元)',
                '优惠金额(元)',
                '实付金额(元)',
                '包装费(元)',
                '支付方式',
                '商品信息',
                '支付状态',
                '下单时间',
                '订单状态',
                '订单来源',
                '订单类型',
                '是否预约单',
                '预约取单时间',
                '商家备注',
                '收货地址'
            ];
        }
    }
    public function columnWidths(): array
    {
        if ($this->params['state'] == 'afterSale') {
            return [
                'A' => 10,
                'B' => 15,
                'C' => 15,
                'D' => 15,
                'E' => 10,
                'F' => 10,
                'G' => 10,
                'H' => 10,
                'I' => 10,
                'J' => 10,
                'K' => 10,
                'L' => 10,
            ];
        }
        return [
            'A' => 10,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 10,
            'J' => 10,
            'K' => 10,
            'L' => 10,
            'M' => 10,
            'N' => 10,
            'O' => 10,
        ];
    }
}
