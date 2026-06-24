<?php
namespace App\Exports;
use App\Models\Order\Discount;
use App\Models\OrderCollect\Receive;
use App\Models\WindowCoupon\CouponReceive;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class OrderCollectReceiveExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $startTime= $params['startTime'];
        $endTime= $params['endTime'];
        $name = $params['name'];
        $data = Receive::with(['ordercollect',
            'member'=> function ($query) {
                $query->select('avatar','id','nickname');
            },'order'])->when($name, function ($q) use ($name) {
            return $q->whereHas('member', function ($q) use ($name) {
                return $q->where('nickname', 'like', "%{$name}%");
            });
        })->when($startTime, function ($q) use ($startTime,$endTime) {
            return $q->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        })->where('uniacid', $uniacid)->orderBy('id', 'desc')->get();
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $newData[]=array(
                'orderSn'=>$v['order']['orderSn'],
                'store'=>$v['order']['store']['name'],
                'nickname'=>$v['member']['nickname'].'('.$v['member']['id'].')'.$v['member']['mobile'],
                'time'=>$v['order']['created_at'],
                'money'=>$v['order']['money'],
                'collectNum'=>$v['order']['collectNum'],
                'orderType'=>'外卖订单',
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            '订单号',
            '所属门店',
            '用户信息',
            '支付时间',
            '实付金额',
            '集点数量',
            '订单类型',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 30,
            'D' => 30,
            'E' => 10,
            'F' => 10,
            'G' => 15
        ];
    }
}
