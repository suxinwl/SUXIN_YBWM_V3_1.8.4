<?php

namespace App\Models;

use App\Models\Member\MemberBase;
use App\Models\Store\Account;
use App\Models\Store\AccountLog;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ConfigService;
use App\Models\Partner;
use App\Models\MemberAccount;
use App\Models\MemberAccountLog;
class PartnerOrder extends BaseModel
{
    protected $table = 'partner_order';
    protected $fillable = ['uniacid', 'orderSn', 'storeId', 'state', 'userId', 'partnerId', 'orderMoney', 'money', 'state', 'level', 'isPay', 'isRefund', 'isBill'];
    use HasFactory;
    protected $appends = [
        'stateFormat'
    ];
    public function partner()
    {
        return $this->hasOne(MemberBase::class, 'id', 'partnerId')->select(['id', 'nickname', 'mobile']);
    }
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

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->select(['id', 'nickname', 'mobile']);
    }
    public function store()
    {
        return $this->hasOne(StoreBase::class, 'id', 'storeId');
    }

    public static function savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$partnerId,$level,$orderMoney,$money)
    {
        if(empty($storeId)||empty($orderMoney)||empty($money)){
            return true;
        }
        $data=[
            'orderSn'=>$orderSn,
            'uniacid'=>$uniacid,
            'storeId'=>$storeId,
            'userId'=>$userId,
            'partnerId'=>$partnerId,
            'orderMoney'=>$orderMoney,
            'money'=>$money,
            'state'=>6,
            'level'=>$level,
            'isPay'=>1,
            'isBill'=>6,
        ];
        PartnerOrder::create($data);
        $memberAccount=MemberAccount::where('userId',$partnerId)->first();
        if (!$memberAccount) {
            return true;
        }
        $canWithdrawalAmount=bcadd($memberAccount->canWithdrawalAmount, $data['money'],2);
        $memberAccount->canWithdrawalAmount=$canWithdrawalAmount;
        $memberAccount->save();

        MemberAccountLog::create([
            'uniacid' => $uniacid,
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
        return true;
    }
    public static function saveStorePartnerOrder($uniacid,$storeId,$userId,$orderSn,$level,$orderMoney,$money)
    {
        if(empty($storeId)||empty($orderMoney)||empty($money)){
            return true;
        }
        $data=[
            'orderSn'=>$orderSn,
            'uniacid'=>$uniacid,
            'storeId'=>$storeId,
            'userId'=>$userId,
            'orderMoney'=>$orderMoney,
            'money'=>$money,
            'state'=>6,
            'level'=>$level,
            'isPay'=>1,
            'isBill'=>6,
            'type'=>1,
        ];
        StorePartnerOrder::create($data);
        $storeAccount=Account::where('storeId',$storeId)->first();
        $commissionAmount=bcadd($storeAccount->commission_amount, $data['money'],2);
        $storeAccount->commission_amount=$commissionAmount;
        $storeAccount->save();

        AccountLog::create([
            'uniacid' => $uniacid,
            'channel' => AccountLog::COMMISSION_AMOUNT,
            'type' => 1,
            'value' => $data['money'],
            'notes' => '分銷商扫码点餐奖励',
            "adminId" => $userId,
            'behavior' =>  AccountLog::CANWITHDRAWALAMOUNT_PARTNER,
            'orderSn' => $orderSn,
            'storeId' => $storeId
        ]);
        return true;
    }

    public static function storePartnerExpenses($uniacid,$storeId,$userId,$orderSn,$level,$orderMoney,$money)
    {
        if(empty($storeId)||empty($orderMoney)||empty($money)){
            return true;
        }
        $data=[
            'orderSn'=>$orderSn,
            'uniacid'=>$uniacid,
            'storeId'=>$storeId,
            'userId'=>$userId,
            'orderMoney'=>$orderMoney,
            'money'=>$money,
            'state'=>6,
            'level'=>$level,
            'isPay'=>1,
            'isBill'=>6,
            'type'=>0,
        ];
        StorePartnerOrder::create($data);
        $storeAccount=Account::where('storeId',$storeId)->first();
        $commissionAmount=bcsub($storeAccount->commission_amount, $money,2);
        $storeAccount->commission_amount=$commissionAmount;
        $storeAccount->save();

        AccountLog::create([
            'uniacid' => $uniacid,
            'channel' => AccountLog::COMMISSION_AMOUNT,
            'type' => 0,
            'value' => $data['money'],
            'notes' => '分銷商扫码点餐佣金支出',
            "adminId" => $userId,
            'behavior' =>  AccountLog::DISTRIBUTION_EXPENSES,
            'orderSn' => $orderSn,
            'storeId' => $storeId
        ]);
        return true;
    }
    public static function createPartnerOrder($uniacid,$storeId,$userId,$partnerId,$money,$orderSn){
        try{
            $config = ConfigService::getChannelConfig('distributor', $uniacid,0);
            if($config['storeType']==2&&!in_array($storeId,$config['storeIds'])){
                return true;
            }
            $floatNumber =$money; // 浮点数
            $percentage = $config['levelRate']['first']; // 百分比，不带百分号
            // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
            $percentage /= 100;
            // 使用 bcmul 进行精确乘法
            $partnerMoney1 = bcmul($floatNumber, $percentage,2);


            $percentage = $config['levelRate']['second']; // 百分比，不带百分号
            // 将百分比转换为小数，例如："5.5" 转换为 "0.055"
            $percentage /= 100;
            // 使用 bcmul 进行精确乘法
            $partnerMoney2 = bcmul($floatNumber, $percentage,2);


            if($config['storeDistribution']==1){
                if ($config['partnerPaySwitch'] == 1) {
                    $partner1 = Partner::where('uniacid',$uniacid)->where('userId',$userId)->first();
                    if($partner1){
                        self::savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$userId,1,$money,$partnerMoney1);
                    }
                }
                $model = Member::where('uniacid', $uniacid)->where('id',$userId)->first();
                if ($config['partnerPaySwitch'] == 1) {
                    self::savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$partnerId,1,$money,$partnerMoney1);
                    self::saveStorePartnerOrder($uniacid,$storeId,$userId,$orderSn,2,$money,$partnerMoney2);
                    self::storePartnerExpenses($uniacid,$storeId,$userId,$orderSn,1,$money,$partnerMoney1);
                    self::storePartnerExpenses($uniacid,$storeId,$userId,$orderSn,2,$money,$partnerMoney2);
                }else{
                    if($partnerId){
                        self::savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$partnerId,1,$money,$partnerMoney1);
                        $partnerModel = Member::where('uniacid', $uniacid)->where('id',$partnerId)->first();

                        self::saveStorePartnerOrder($uniacid, $partnerModel->storeId, $userId, $orderSn, 2, $money, $partnerMoney2);

                        self::storePartnerExpenses($uniacid,$storeId,$userId,$orderSn,1,$money,$partnerMoney1);
                        self::storePartnerExpenses($uniacid,$storeId,$userId,$orderSn,2,$money,$partnerMoney2);
                    }else{
                        if($model->storeId==$storeId){
                            self::saveStorePartnerOrder($uniacid,$storeId,$userId,$orderSn,1,$money,$partnerMoney1);
                            self::storePartnerExpenses($uniacid,$model->storeId,$userId,$orderSn,1,$money,$partnerMoney1);
                        }else{
                            self::saveStorePartnerOrder($uniacid,$model->storeId,$userId,$orderSn,1,$money,$partnerMoney1);
                            self::storePartnerExpenses($uniacid,$storeId,$userId,$orderSn,1,$money,$partnerMoney1);
                        }
                    }
                }

            }else{
                if ($config['partnerPaySwitch'] == 1) {
                    $partner1 = Partner::where('uniacid',$uniacid)->where('userId',$userId)->first();
                    if($partner1){
                        self::savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$userId,1,$money,$partnerMoney1);
                    }
                    if ($config['level'] == 2&&$partnerId) {
                        $parent = Partner::where('uniacid', $uniacid)->where('userId', $partnerId)->first();
                        if ($parent) {
                            self::savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$partnerId,2,$money,$partnerMoney2);
                        }
                    }

                }else{
                    if ($partnerId) {
                        $partner = Partner::where('uniacid',$uniacid)->where('userId',$partnerId)->first();
                        if(empty($partner)){
                            return true;
                        }
                        self::savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$partnerId,1,$money,$partnerMoney1);
                        //二级分销
                        $partner = Partner::where('uniacid', $uniacid)->where('userId', $partnerId)->first();
                        if ($partner) {
                            if ($config['level'] == 2&&$partner->parentId) {
                                $parent = Partner::where('uniacid', $uniacid)->where('userId', $partnerId)->first();
                                if ($parent) {
                                    self::savePartnerOrder($uniacid,$storeId,$userId,$orderSn,$parent->parentId,1,$money,$partnerMoney2);
                                }
                            }
                        }
                    }
                }

            }
        }catch (Exception $e) {
             file_put_contents('partnerOrder.log',$e->getMessage() . " 在文件 " . $e->getFile() . " 的第 " . $e->getLine() . " 行");
        }
        return true;
    }
}
