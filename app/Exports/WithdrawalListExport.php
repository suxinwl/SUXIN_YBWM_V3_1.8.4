<?php
namespace App\Exports;
use App\Models\BulkOrder;
use App\Models\InStore\Order\Order;
use App\Models\Order\TakeOutOrder;
use App\Models\StoreWithdrawal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class WithdrawalListExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $user = $params['user'];
        $data = StoreWithdrawal::where('uniacid', $uniacid)
            ->when($storeId, function ($q) use ($storeId) {
                $q->where('storeId',  $storeId);
            })
            ->when($user, function ($q) use ($user) {
                if ($user->isAdmin == 0) {
                    if (!empty($user->storeId)) {
                        $q->whereIn('storeId', $user->storeId);
                    }
                }
                return $q;
            })
            ->where('created_at', '>=', $params['timeArr']['startTime'])
            ->where('created_at', '<=', $params['timeArr']['endTime'])
            ->where(function ($q) use ($params) {
                if ($params['state'] == 'review') {
                    $q->review();
                }
                if ($params['state'] == 'pass') {
                    $q->pass();
                }
                if ($params['state'] == 'reject') {
                    $q->reject();
                }
                if ($params['state'] == 'cancel') {
                    $q->cancel();
                }
                if ($params['startTime'] && $params['endTime']) {
                    $q->where('created_at', '>=', $params['startTime'])->where('created_at', '<=', $params['endTime']);
                }
                return $q;
            })->get();
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
