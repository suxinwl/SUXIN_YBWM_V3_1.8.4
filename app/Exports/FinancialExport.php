<?php
namespace App\Exports;
use App\Models\BulkOrder;
use App\Models\InStore\Order\Order;
use App\Models\Order\TakeOutOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class FinancialExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $user = $params['user'];
        $keyword= $params['keyword'];
        $tableKeyword= $params['tableKeyword'];
        $mahId= $params['mahId'];
        $data = $lists =  OrderIndex::where('uniacid', $uniacid)
            ->when(($storeId), function ($q) use ($storeId) {
                if (is_array($storeId)) {
                    $q->whereIn('storeId', $storeId);
                } else {
                    $q->where('storeId', $storeId);
                }
            })
            ->where('payType', '>', 10)
            ->where('payType', '<', 30)
            ->when($params['startTime'], function ($q) use ($params) {
                return $q->where('payTime', '>=', $params['startTime']);
            })
            ->when($mahId, function ($q) use ($mahId) {
                return $q->where('mchId', 'like', "%$mahId%");
            })
            ->when($params['endTime'], function ($q) use ($params) {
                return $q->where('payTime', '<=', $params['endTime']);
            })
            ->when($params['payChannel'], function ($q) use ($params) {
                return $q->where('payChannel', '<=', $params['payChannel']);
            })
            ->orderBy('id', 'desc')
            ->groupBy('thirdNo')->get();
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $newData[]=array(
                'created_at'=>$v['created_at'],
                'payChannelFormat'=>$v['payChannelFormat'],
                'store'=>$v['subOrder']['store']['name'],
                'money'=>$v['subOrder']['money'],
                'payTypeFormat'=>$v['payTypeFormat'],
                'mchId'=>$v['mchId'],
                'thirdNo'=>$v['thirdNo'],
                'payStateFormat'=>$v['payStateFormat']
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            '支付时间',
            '收款方',
            '所属门店',
            '支付金额',
            '支付方式',
            '商户号',
            '第三方订单号',
            '支付状态',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 10,
            'C' => 15,
            'D' => 12,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 12,
        ];
    }
}
