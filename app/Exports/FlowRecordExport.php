<?php

namespace App\Exports;

use App\Models\BulkOrder;
use App\Models\InStore\Order\Order;
use App\Models\Store\AccountLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings; //导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FlowRecordExport implements FromArray, WithHeadings, WithColumnWidths
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
        $channel = 1;
        $user = $params['user'];
        $data =  AccountLog::with(['store' => function ($q) {
            return $q->select(['id', 'name'])->withTrashed();
        }])->where('uniacid', $uniacid)
            ->where("channel", $channel)
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("storeId", $storeId);
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
            ->where(function ($q) use ($params) {
                if ($params['startTime'] && $params['endTime']) {
                    $q->where('created_at', '>=', $params['timeArr']['startTime'])->where('created_at', '<=', $params['timeArr']['endTime']);
                }
                return $q;
            })->orderBy('id', 'desc')->get();

        $data = empty($data) ? array() : $data->toArray();

        if (empty($data)) {
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData = [];
        foreach ($data as $k => $v) {
            $newData[] = array(
                'id' => $v['store']['id'],
                'name' => $v['store']['name'],
                'format' => $v['format'],
                'value' => $v['value'],
                'created_at' => $v['created_at'],
                'atLast' => $v['atLast'],
                'notes' => $v['notes']
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array
    {
        return [
            '门店ID',
            '门店名称',
            '业务类型',
            '金额(元)',
            '变更时间',
            '账户余额(元)',
            '备注'
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 15,
            'C' => 15,
            'D' => 10,
            'E' => 12,
            'F' => 15,
            'G' => 15
        ];
    }
}
