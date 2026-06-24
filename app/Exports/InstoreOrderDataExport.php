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
class InstoreOrderDataExport implements FromArray,WithHeadings,WithColumnWidths{
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
        $data = Order::with(['subOrder', 'orderIndex', 'goods', 'table'])
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where('storeId', $storeId);
            })
            ->when($params['diningType'], function ($q) use ($params) {
                return $q->where('diningType', $params['diningType']);
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
            ->where('uniacid', $uniacid)
            ->when($params['state'], function ($q) use ($params) {
                if (empty($params['state'])) {
                    return $q->where(function ($q) {
                        return $q->where(function ($q) {
                            return $q->whereNull("prentOrderSn")->whereIn('diningType', [5, 6]);
                        })->orWhere(function ($q) {
                            return $q->whereNotNull("prentOrderSn")->where('diningType', 4)->whereIn('state', [1, 2]);
                        })->orWhere(function ($q) {
                            return $q->whereNull("prentOrderSn")->where('diningType', 4)->whereIn('state', [3, 5, 6]);
                        });
                    });
                }
                if ($params['state'] == 'unReceived') {
                    return $q->unReceived();
                }
                if ($params['state'] == 'making') {
                    return $q->whereIn('diningType', [5, 6])->where('state', 3)->whereNull("prentOrderSn");
                }
                if ($params['state'] == 'waiting') {
                    return $q->whereIn('diningType', [5, 6])->where('state', 4)->whereNull("prentOrderSn"); //待取单
                }
                if ($params['state'] == 'dining') {
                    return $q->where('diningType', 4)->where('state', 3)->whereNull("prentOrderSn");
                }
                if ($params['state']== 'complete') {
                    return $q->where('state', 6)->whereNull("prentOrderSn");
                }
                if ($params['state'] == 'close') {
                    return $q->where('state', 0)->whereNull("prentOrderSn");
                }
                if ($params['state'] == 'refund') {
                    return $q->where('state', 8)->whereNull("prentOrderSn");
                }
            })
            ->when($keyword, function ($q) use ($keyword) {
                return $q->where(
                    function ($q) use ($keyword) {
                        return $q->where('orderSn', "like", "%$keyword%")
                            ->orWhere(FacadesDB::raw('CONCAT(pickFix, pickNo)'), "like", "%{$keyword}%");
                    }
                );
            })
            ->when($params['timeType'], function ($q) use ($params) {
                return $q->where($request->timeChannel ?? 'created_at', '>=', $params['timeArr']['startTime'])
                    ->where($request->timeChannel ?? 'created_at', '<=', $params['timeArr']['endTime']);
            })
            ->when($tableKeyword, function ($q) use ($tableKeyword) {
                return $q->whereHas('table', function ($q) use ($tableKeyword) {
                    return $q->where('name', "like", "%{$tableKeyword}%");
                });
            })
            ->when($params['source'], function ($q) use ($params) {
                return $q->where('source', appType($params['source']));
            })
            ->when($params['payType'], function ($q) use ($params) {
                return $q->whereHas('orderIndex', function ($q) use ($params) {
                    if ($params['payType']== 'wexin') {
                        return $q->weixin();
                    }
                    if ($params['payType'] == 'ali') {
                        return $q->ali();
                    }
                    if ($params['payType'] == 'balance') {
                        return $q->balance();
                    }
                    if ($params['payType'] == 'cash') {
                        return $q->cash();
                    }
                    return $q;
                });
            })
            ->when($params['source'], function ($q) use ($params) {
                return $q->where('source', appType($params['source']));
            })
            ->when($userKeyword, function ($q) use ($userKeyword) {
                return $q->whereHas('user', function ($q) use ($userKeyword) {
                    return $q->where('mobile', "like", "%$userKeyword%")->orWhere('nickname', "like", "%$userKeyword%");
                });
            })
            ->where("goodsNum", ">", 0)
            ->where("uniacid", $uniacid)
            ->groupBy('orderSn')
            ->orderBy('id', 'desc')->get();
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $tableArr=[$v['table']['area']['name'],$v['table']['type']['name'],$v['table']['name']];
            $tableArr=array_filter($tableArr);
            $tableStr=implode('-',$tableArr);
            $newData[]=array(
                'pickNo'=>$v['pickNo'],
                'diningTypeFormat'=>$v['diningTypeFormat'],
                'store'=>$v['store']['name'],
                'nickname'=>$v['user']['nickname'],
                'mobile'=>$v['user']['mobile'],
                'money'=>$v['money'],
                'payMoney'=>$v['payMoney'],
                'discountMoney'=>$v['discountMoney'],
                'stateFormat1'=>$v['state']>1?'已支付':'未支付',
                'payTypeFormat'=>$v['payTypeFormat'],
                'goodsFormat'=>$v['goodsFormat'],
                'created_at'=>$v['created_at'],
                'diningTypeFormat1'=>$v['diningTypeFormat'],
                'stateFormat'=>$v['stateFormat'],
                'packagingFormat'=>$v['packagingFormat'],
                'area'=>$tableStr,
                'people'=>$v['people'].'人',
                'sourceFormat'=>$v['sourceFormat'],
                'notes'=>$v['notes'],
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
            return [
                '流水号',
                '订单类型',
                '所属门店',
                '下单人信息',
                '手机号码',
                '应付金额(元)',
                '实付金额(元)',
                '优惠金额(元)',
                '支付状态',
                '支付方式',
                '商品信息',
                '下单时间',
                '就餐类型',
                '订单状态',
                '就餐方式',
                '桌位号',
                '就餐人数',
                '订单来源',
                '商家备注',
            ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 10,
            'C' => 15,
            'D' => 12,
            'E' => 12,
            'F' => 6,
            'G' => 6,
            'H' => 6,
            'I' => 8,
            'J' => 8,
            'K' => 15,
            'L' => 10,
            'M' => 10,
            'N' => 10,
            'O' => 10,
        ];
    }
}
