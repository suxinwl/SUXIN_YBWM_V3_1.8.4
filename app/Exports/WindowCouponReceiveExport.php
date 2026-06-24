<?php
namespace App\Exports;
use App\Models\WindowCoupon\CouponReceive;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class WindowCouponReceiveExport implements FromArray,WithHeadings,WithColumnWidths{
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
        $storeId = $params['storeId'];
        $data = CouponReceive::with(['member','window'])
        ->where('uniacid', $uniacid)
        ->where('storeId', $storeId)
        ->when($name, function ($q) use ($name) {
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
            $content='';
            if($v['balance']>0){
                $content.='赠送金额'.$v['data']['balance'].',';
            }
            if($v['integral']>0){
                $content.='赠送积分'.$v['data']['integral'].',';
            }
            if(!empty($v['coupon'])){
                foreach ($v['coupon'] as $vo){
                    $content.=$vo['name'].'×'.$vo['num'].',';
                }

            }
            $content = mb_substr($content, 0, -1, "UTF-8");
            $newData[]=array(
                'nickname'=>$v['member']['nickname'].'('.$v['member']['id'].')'.$v['member']['mobile'],
                'time'=>$v['created_at'],
                'type'=>'首页-弹窗发券',
                'content'=>$content,
                'state'=>'已领取',
                'activitieId'=>$v['data']['id'],
                'activitieName'=>$v['data']['name'],
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            '领取人信息',
            '领取时间',
            '领取方式',
            '领取内容',
            '领取状态',
            '活动ID',
            '活动名称'
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
