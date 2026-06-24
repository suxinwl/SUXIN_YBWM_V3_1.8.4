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
use App\Models\Statistics\Balance;
class StoredValueConsumptionExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $user = $params['user'];
        $model = new Balance(['uniacid' => $uniacid, 'storeId' => $this->storeId]);
        $data = $model['data']['list'];
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $newData[]=array(
                'day'=>$v['day'],
                'store'=>$v['store']['name'],
                'balanceMoney'=>$v['balanceMoney']?:'0',
                'balanceOrder'=>$v['balanceOrder']?:'0',
                'balanceRefundOrder'=>$v['balanceRefundOrder']?:'0',
                'balanceRefundMoney'=>$v['balanceRefundMoney']?:'0',
                'storedValueCount'=>$v['storedValueCount']?:'0',
                'storedValueCapital'=>$v['storedValueCapital']?:'0',
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            '日期',
            '门店名称',
            '余额支付订单数',
            '余额支付金额',
            '余额退款订单数',
            '余额退款金额',
            '储值订单数',
            '储值金额',
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
