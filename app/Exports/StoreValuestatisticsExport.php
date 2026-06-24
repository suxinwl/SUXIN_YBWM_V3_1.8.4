<?php
namespace App\Exports;
use App\Models\BulkOrder;
use App\Models\InStore\Order\Order;
use App\Models\Order\TakeOutOrder;
use App\Models\PersionPayOrder;
use App\Models\StatisticsDay;
use App\Models\StoredValueOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Models\Statistics\StoredValueData;
class StoreValuestatisticsExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $user = $params['user'];
        $model = new StoredValueData(['uniacid' => $uniacid]);
        $data = $model['statisticsDay'];
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $newData[]=array(
                'id'=>$v['id'],
                'day'=>$v['day'],
                'startBalance'=>$v['startBalance'],
                'storedValueCapital'=>$v['storedValueCapital'],
                'storedValueGive'=>$v['storedValueGive'],
                'storedValue'=>$v['storedValue'],
                'balanceMoney'=>$v['balanceMoney'],
                'balance'=>$v['balance'],
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            'ID',
            '统计日期',
            '期初金额',
            '储值本金',
            '储值赠送',
            '收入合计(元)',
            '支出合计(元)',
            '期末金额',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 8,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];
    }
}
