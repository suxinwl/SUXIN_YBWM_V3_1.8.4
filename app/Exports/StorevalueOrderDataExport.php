<?php

namespace App\Exports;

use App\Models\BulkOrder;
use App\Models\InStore\Order\Order;
use App\Models\Order\TakeOutOrder;
use App\Models\PersionPayOrder;
use App\Models\StoredValueOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings; //导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class StorevalueOrderDataExport implements FromArray, WithHeadings, WithColumnWidths
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
        $data = StoredValueOrder::with(['user' => function ($q) {
            return $q->select(['id', 'nickname', 'mobile', 'avatar']);
        }, 'store' => function ($q) {
            return $q->select(['id', 'name']);
        }, 'orderIndex'])
            ->where('uniacid', $uniacid)
            ->where('state', 2)
            ->when($keyword, function ($q) use ($keyword) {
                return $q->whereHas('user', function ($q) use ($keyword) {
                    return $q->where('nickname', 'like', "%$keyword%")->orWhere('mobile', 'like', "%$keyword%");
                });
            })->when($params['score'], function ($q) use ($params) {
                return $q->where('score', $params['score']);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (empty($user->storeId)) {
                        $q->where('storeId', 0);
                    } else {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->when($params['timeType'], function ($q) use ($params) {
                return $q->where('created_at', '>=', $params['timeArr']['startTime'])
                    ->where('created_at', '<=', $params['timeArr']['endTime']);
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->orderBy('id', 'desc')->get();
        $data = empty($data) ? array() : $data->toArray();
        if (empty($data)) {
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData = [];
        foreach ($data as $k => $v) {
            $newData[] = array(
                'orderSn' => $v['orderSn'],
                'store' => $v['store']['name'],
                'nickname' => $v['user']['nickname'],
                'mobile' => $v['user']['mobile'],
                'money1' => $v['money'],
                'money' => $v['money'],
                'money2' => '充' . $v['money'],
                'giveFormat' => '赠送余额' . $v['data']['balanceGive'] . '赠送积分' . $v['data']['integralGive'],
                'payStateFormat' => bcadd($v['money'], $v['data']['balanceGive']),
                'state' => $v['state'] > 1 ? '已支付' : '未支付',
                'sourceFormat' => $v['sourceFormat'],
                'payTypeFormat' => '微信支付',
                'created_at' => $v['created_at'],
                'appointment' => '会员储值',
                'stateFormat' => $v['stateFormat'],
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array
    {
        return [
            '订单编号',
            '储值门店',
            '下单人信息',
            '手机号码',
            '应付金额(元)',
            '实付金额(元)',
            '储值金额(元)',
            '储值赠送',
            '到账金额(元)',
            '支付状态',
            '订单来源',
            '支付方式',
            '储值时间',
            '订单类型',
            '订单状态',
        ];
    }
    public function columnWidths(): array
    {
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
        ];
    }
}
