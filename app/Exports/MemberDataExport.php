<?php
namespace App\Exports;
use App\Models\BulkOrder;
use App\Models\Member;
use App\Models\Member\MemberQrCode;
use App\Models\Visit;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;//导出excle表头
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
class MemberDataExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $mobile = $params['mobile'];
        $keyword= $params['keyword'];
        $storeId =$params['storeId'];
        $data=Member::with(['account','vip'])->where('uniacid', $uniacid)
        ->where('storeId',$storeId)
        ->where(function ($q) use ($params,$keyword, $uniacid) {
            if ($keyword) {
                if (mb_strlen($keyword, 'UTF8') == 18) {
                    $model = MemberQrCode::where('uniacid', $uniacid)
                        ->where('qrcode', $keyword)
                        ->where('expired', '>=', date("Y-m-d H:i:s"))
                        ->first();
                    return $q->where('id', $model->userId ?? 0);
                } else {
                    $q->where(function ($q) use ($params, $keyword) {
                        $q->orWhere('id', $keyword);
                        $q->orWhere('mobile', 'like', "%$keyword%");
                        $q->orWhere('nickname', 'like', "%$keyword%");
                        $q->orWhere('realname', 'like', "%$keyword%");
                        $q->orWhere('vipCard', 'like', "%$keyword");
                        return $q;
                    });
                }
            }
            if ($params['tourists']) {
                $q->tourists();
            } else {
                $q->members();
            }
            if ($params['score']) {
                $q->where('score', $params['score']);
            }
            if ($params['labelId']) {
                $q->whereHas("label", function ($q) use ($params) {
                    return  $q->where('labelId', $params['labelId']);
                });
            }
            if ($params['groupId']) {
                $q->where("groupId", $params['groupId']);
            }
            if ($params['level']) {
                $q->whereHas('vip', function ($q) use ($params) {
                    $q->where("level", $params['level']);
                });
            }
            if ($params['timeType']) {
                $q->where('created_at', '>=', $params['timeArr']['startTime']);
                $q->where('created_at', '<=', $params['timeArr']['endTime']);
            }
            return $q;
        })->when($params['qrcode'], function ($q) use ($params, $uniacid) {
            $model = MemberQrCode::where('uniacid', $uniacid)
                ->where('qrcode', $params['qrcode'])
                ->where('expired', '>=', date("Y-m-d H:i:s"))
                ->first();
            return $q->where('id', $model->userId ?? 0);
        })->when($mobile, function ($q) use ($mobile) {
            $q->where('mobile', 'like', "%$mobile");
        })->orderBy('id', 'desc')->get();
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $v){
            if(empty($v['sex'])){
                $sexFormat='不详';
            }
            if($v['sex']==1){
                $sexFormat='男';
            }
            if($v['sex']==2){
                $sexFormat='女';
            }
        $newData[]=array(
                'id'=>$v['id'],
                'nickname'=>$v['nickname'],
                'realname'=>$v['realname'],
                'mobile'=>$v['mobile'],
                'vipName'=>$v['vip']['name'].'(VIP'.$v['vip']['level'].')',
                'sexFormat'=>$sexFormat,
                'birthday'=>$v['birthday'],
                'created_at'=>$v['created_at'],
                'balance'=>$v['account']['balance'],
                'integral'=>$v['account']['integral'],
                'exp'=>$v['account']['exp'],
                'updated_at'=>$v['updated_at'],
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            'ID',
            '会员信息',
            '真实姓名',
            '手机号码',
            '会员等级',
            '性别',
            '生日',
            '注册时间',
            '余额',
            '积分',
            '成长值',
            '最后访问时间',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 15,
            'C' => 10,
            'D' => 15,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 10,
            'J' => 10,
            'K' => 10,
            'L' => 10,
        ];
    }
}
