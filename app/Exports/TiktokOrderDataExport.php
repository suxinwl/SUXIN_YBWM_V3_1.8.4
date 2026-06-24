<?php
namespace App\Exports;
use App\Models\BulkOrder;
use App\Models\StoredValueOrder;
use App\Models\TiktokVerifyList;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class TiktokOrderDataExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $user = $params['user'];
        $code= $params['code'];
        $data = TiktokVerifyList::with(['store','admin'])->where('uniacid', $uniacid)
            ->when($code, function ($q) use ($code) {
                return $q->where("code", "like", "%{$code}%");
            })
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("storeId",$storeId);
            })
            ->when($params['startTime'], function ($q) use ($params) {
                return $q->where("created_at", ">=", $params['startTime']);
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
            ->when($params['endTime'], function ($q) use ($params) {
                return $q->where("created_at", "<=", $params['endTime']);
            })->orderBy('id', 'desc')->get();
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $newData[]=array(
                'poi_name'=>$v['poi_name'],
                'name'=>$v['store']['name'],
                'code'=>$v['code'],
                'content'=>$v['content'],
                'created_at'=>$v['created_at'],
                'nickname'=>$v['admin']['nickname'],
                'state'=>$v['money']==1?'已核销':'已撤销核销'
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            '兑换门店',
            '门店名称',
            '抖音核销码',
            '兑换内容',
            '兑换时间',
            '操作人',
            '状态',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 15,
            'C' => 15,
            'D' => 20,
            'E' => 10,
            'F' => 10,
            'G' => 10,
        ];
    }
}
