<?php
namespace App\Exports;
use App\Models\BulkOrder;
use App\Models\Order\TakeOutOrder;
use App\Models\StatisticsOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\Statistics\OrderData;
class StatisticsOrderDataExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $model = new OrderData(['uniacid' => $uniacid, 'storeId' => $storeId]);
        $data =$model['dataList'];
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];

        foreach ($data as $k=>$v){
            $newData[]=array(
                'day'=>$v['day'],
                'orderCount'=>$v['orderCount'],
                'sellMoney'=>$v['sellMoney'],
                'money'=>$v['money'],
                'onlineMoney'=>$v['onlineMoney'],
                'onlineOrder'=>$v['onlineOrder']?:'0',
                'balanceMoney'=>$v['balanceMoney'],
                'balanceOrder'=>$v['balanceOrder']?:'0',
                'cashMoney'=>$v['cashMoney'],
                'cashOrder'=>$v['cashOrder']?:'0',
                'zitiMoney'=>$v['zitiMoney'],
                'ziti'=>$v['ziti']?:'0',
                'waisongMoney'=>$v['waisongMoney'],
                'waisong'=>$v['waisong']?:'0',
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            '日期',
            '订单数',
            '营业额',
            '支付金额',
            '线上支付订单金额',
            '线上支付订单',
            '余额支付订单金额',
            '余额支付订单',
            '现金支付订单金额',
            '现金支付订单',
            '自取金额',
            '自取单数',
            '外送金额',
            '外送单数',
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
