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
use App\Models\Store\Account;
class StoreAccountExport implements FromArray,WithHeadings,WithColumnWidths{
    private $params;
    public function __construct($params){
        $this->params=$params;
    }
    public function array(): array{
        $params = $this->params;
        $uniacid = $params['uniacid'];
        $storeId = $params['storeId'];
        $user = $params['user'];
        $data = Account::with(['store'])
            ->when($storeId, function ($q) use ($storeId) {
                return $q->where("storeId", $storeId);
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
            ->orderBy('storeId', 'asc')->get();
        $data=empty($data)?array():$data->toArray();
        if(empty($data)){
            throw new BadRequestException('暂无可导出的数据');
        }
        $newData=[];
        foreach ($data as $k=>$v){
            $newData[]=array(
                'id'=>$v['id'],
                'store'=>$v['store']['name'],
                'total_amount'=>$v['total_amount'],
                'amount'=>$v['amount'],
                'withdrawalAmount'=>$v['withdrawalAmount'],
                'withdrawalCompleteAmount'=>$v['withdrawalCompleteAmount'],
            );
        }
        return $newData;
    }
    //添加指定表头
    public function headings(): array{
        return [
            'id',
            '门店名称',
            '账户余额(元)',
            '可提现金额',
            '提现中金额',
            '已提现金额',
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
            'F' => 12
        ];
    }
}
