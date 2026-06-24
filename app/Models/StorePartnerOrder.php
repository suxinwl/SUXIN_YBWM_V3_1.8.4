<?php

namespace App\Models;

use App\Models\Member\MemberBase;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ConfigService;
use App\Models\Partner;
use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
class StorePartnerOrder extends BaseModel
{
    protected $table = 'store_partner_order';
    protected $fillable = ['uniacid', 'orderSn', 'storeId', 'state', 'userId', 'partnerId', 'orderMoney', 'money', 'state', 'level', 'isPay', 'isRefund', 'isBill','type'];
    use HasFactory;
    protected $appends = [
        'stateFormat'
    ];

    public function getStateFormatAttribute()
    {
        $data = [
            0 => "已取消",
            1 => "待支付",
            2 => "进行中",
            3 => "进行中",
            4 => "进行中",
            5 => "进行中",
            6 => "已完成",
            10 => "已完成",
            7 => "用户申请退款",
            8 => "已退款"
        ];
        return $data[$this->state];
    }


    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }

    public static function createPartnerOrder($uniacid,$storeId,$userId,$partnerId,$money,$orderSn){
        $config = ConfigService::getChannelConfig('distributor', $uniacid,0);
        if($config['storeType']==2&&!in_array($storeId,$config['storeIds'])){
            return true;
        }
        if ($partnerId) {
            $partner = Partner::where('uniacid',$uniacid)->where('userId',$partnerId)->first();
            if(empty($partner)){
                return true;
            }
            //二级分销
            $partner = Partner::where('uniacid', $uniacid)->where('userId', $partnerId)->first();
            if ($partner) {
                $floatNumber =$money; // 浮点数
                $percentage = $config['levelRate']['first']; // 百分比，不带百分号
                // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                $percentage /= 100;
                // 使用 bcmul 进行精确乘法
                $partnerMoney = bcmul($floatNumber, $percentage,2);
                $parents[0] = [
                    'level' => 1,
                    'partnerId' => $partnerId,
                    'money' => $partnerMoney,
                    'balance' => $partner->balance
                ];
                if ($config['level'] == 2&&$partner->parentId) {
                    $parent = Partner::where('uniacid', $uniacid)->where('userId', $partnerId)->first();
                    if ($parent) {
                        $floatNumber =$money; // 浮点数
                        $percentage = $config['levelRate']['second']; // 百分比，不带百分号
                        // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                        $percentage /= 100;
                        // 使用 bcmul 进行精确乘法
                        $partnerMoney = bcmul($floatNumber, $percentage,2);
                        $parents[1] = [
                            'level' => 2,
                            'partnerId' => $parent->parentId ,
                            'money' => $partnerMoney,
                            'balance' => $partner->balance
                        ];
                    }
                }
            }
        }
        if ($config['partnerPaySwitch'] == 1) {
            $partner1 = Partner::where('uniacid',$uniacid)->where('userId',$userId)->first();
            if($partner1){
                //内购
                $floatNumber =$money; // 浮点数
                $percentage = $config['levelRate']['first']; // 百分比，不带百分号
                // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                $percentage /= 100;
                // 使用 bcmul 进行精确乘法
                $partnerMoney = bcmul($floatNumber, $percentage,2);
                $parents[0] = [
                    'level' => 1,
                    'partnerId' => $userId,
                    'money' => $partnerMoney,
                    'balance' => $partner1->balance
                ];
                if ($config['level'] == 2&&$partnerId) {
                    $parent = Partner::where('uniacid', $uniacid)->where('userId', $partnerId)->first();
                    if ($parent) {
                        $floatNumber =$money; // 浮点数
                        $percentage = $config['levelRate']['second']; // 百分比，不带百分号
                        // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
                        $percentage /= 100;
                        // 使用 bcmul 进行精确乘法
                        $partnerMoney = bcmul($floatNumber, $percentage,2);
                        $parents[1] = [
                            'level' => 2,
                            'partnerId' => $partnerId,
                            'money' => $partnerMoney,
                            'balance' => $partner->balance
                        ];
                    }
                }
            }

        }

        foreach ($parents as $key =>$v) {
            $data=[];
            $data=[
                'orderSn'=>$orderSn,
                'uniacid'=>$uniacid,
                'storeId'=>$storeId,
                'userId'=>$userId,
                'partnerId'=>$v['partnerId'],
                'orderMoney'=>$money,
                'money'=>$v['money'],
                'state'=>6,
                'level'=>$v['level'],
                'isPay'=>1,
                'isBill'=>6,
            ];
            PartnerOrder::create($data);
            $memberAccount=MemberAccount::where('userId',$v['partnerId'])->first();
            $canWithdrawalAmount=bcadd($memberAccount->canWithdrawalAmount, $data['money'],2);
            $memberAccount->canWithdrawalAmount=$canWithdrawalAmount;
            $memberAccount->save();

            MemberAccountLog::create([
                'uniacid' => $uniacid,
                'userId' => $v['partnerId'],
                'cat' => 'balance',
                'channel' => MemberAccountLog::CHANNEL_BALANCE,
                'type' => 1,
                'value' => $data['money'],
                'notes' => '分銷商扫码点餐奖励',
                'atLast' => $memberAccount->balance,
                "adminId" => $userId,
                'behavior' =>  MemberAccountLog::CANWITHDRAWALAMOUNT_PARTNER,
                'orderSn' => $orderSn,
                'storeId' => $storeId
            ]);
        }

    }
}
