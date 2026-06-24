<?php
namespace App\Exports;
use App\Models\BulkOrder;
use App\Models\InStore\Order\Order;
use App\Models\Order\TakeOutOrder;
use App\Models\PersionPayOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class PersonpayOrderDataExport implements FromArray,WithHeadings,WithColumnWidths{
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
        $userKeyword= $params['tableKeyworduserKeyword'];
        $data =PersionPayOrder::with(['orderIndex', 'user' => function ($q) {
            return $q->select(['id', 'nickname', 'mobile', 'avatar']);
        }, 'store' => function ($q) {
            return $q->select(['id', 'name']);
        }, 'orderIndex'])
            ->where('uniacid', $uniacid)
            ->whereIn('state', [6, 8])
            ->when($keyword, function ($q) use ($keyword) {
                return $q->where(
                    function ($q) use ($keyword) {
                        return $q->where('orderSn', "like", "%$keyword%");
                    }
                );
            })
            ->when($params['payType'], function ($q) use ($params) {
                return $q->whereHas('orderIndex', function ($q) use ($params) {
                    if ($params['payType']== 'wexin') {
                        return $q->weixin();
                    }
                    if ($params['payType'] == 'ali') {
                        return $q->ali();
                    }
                    if ($params['payType']== 'balance') {
                        return $q->balance();
                    }
                    return $q;
                });
            })
//            ->when($params['timeType'], function ($q) use ($params) {
//                return $q->where('created_at', '>=', $params['timeArr']['startTime'])
//                    ->where('created_at', '<=', $params['timeArr']['endTime']);
//            })
            ->when($params['source'], function ($q) use ($params) {
                return $q->where('score', appType($params['source']));
            })
            ->when($userKeyword, function ($q) use ($userKeyword) {
                return $q->whereHas('user', function ($q) use ($userKeyword) {
                    return $q->where('mobile', "like", "%$userKeyword%")
                        ->orWhere('nickname', "like", "%$userKeyword%");
                });
            })
            ->when($params['state'], function ($q) use ($params) {
                if ($params['state'] == 'pay') {
                    return $q->where('state', 6);
                }
                if ($params['state'] == 'close') {
                    return $q->where('state', 0);
                }
                if ($params['state']== 'refund') {
                    return $q->where('state', 8);
                }
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
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
            ->orderBy('id', 'desc')->get();

        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $newData[]=array(
                'pickNo'=>$v['pickNo'],
                'store'=>$v['store']['name'],
                'nickname'=>$v['user']['nickname'],
                'mobile'=>$v['user']['mobile'],
                'money'=>$v['money'],
                'money1'=>$v['money'],
                'discountMoney'=>$v['discountMoney'],
                'state'=>$v['state']>1?'已支付':'未支付',
                'created_at'=>$v['created_at'],
                'sourceFormat'=>$v['sourceFormat'],
                'payTypeFormat'=>$v['payTypeFormat'],
                'format'=>'当面付',
                'stateFormat'=>$v['stateFormat'],
                'notes'=>$v['notes']
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            '流水号',
            '所属门店',
            '下单人信息',
            '手机号码',
            '应付金额(元)',
            '实付金额(元)',
            '优惠金额(元)',
            '支付状态',
            '下单时间',
            '订单来源',
            '支付方式',
            '订单类型',
            '订单状态',
            '订单备注',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 15,
            'C' => 12,
            'D' => 12,
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
