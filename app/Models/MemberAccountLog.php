<?php

namespace App\Models;

use App\Events\MemberAccountEvent;
use App\Events\OrderMessageEvent;
use Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberAccountLog extends BaseModel
{
    protected $table = 'member_account_log';
    use HasFactory;
    const BASE = 0; //系统调整
    const CHANNEL_INTEGRAL = 1; //积分
    const CHANNEL_BALANCE = 2; //余额
    const CHANNEL_CANWITHDRAWALAMOUNT = 3; //可提现金额
    const CHANNEL_FREEZEAMOUNT = 4; //冻结中金额
    const CHANNEL_WITHDRAWALAMOUNT = 5; //体现中金额
    const CHANNEL_WITHDRAWALCOMPLETEAMOUNT = 6; //已提现金额

    const CHANNEL_EXP = 7; //成长值
    const INTEGRAL_VIP_GIVE = 100; // 会员赠送积分
    const INTEGRAL_ORDER_GIVE = 101; // 会员赠送积分
    const INTEGRAL_BUY_GIVE = 102; // 会员充值赠送积分
    const INTEGRAL_SIGNIN_GIVE = 103; // 签到赠送积分
    const INTEGRAL_GIFT_BIG = 104; // 新人礼包赠送
    const INTEGRAL_PAYGIFT = 105; //支付有礼奖励积分奖励
    const INTEGRAL_PAYGIFT_REFUND = 106; // 支付有礼奖励积分撤回
    const INTEGRAL_ORDERCOLLECT = 107; //集点有礼奖励积分奖励
    const INTEGRAL_ORDERCOLLECT_REFUND = 108; // 集点有礼奖奖励积分撤回
    const INTEGRAL_SIGNIN_CONTINUOUS = 109; // 连续签到赠送积分
    const INTEGRAL_ORDER_PAY = 110; // 积分兑换
    const INTEGRAL_ORDER_REFUND = 111; // 积分退回
    const INTEGRAL_BIRTHDAYGIFT_PERFECT = 112; // 完善资料积分奖励
    const INTEGRAL_BIRTHDAYGIFT_BIRTHDA = 113; // 生日有礼积分奖励
    const INTEGRAL_OLDWITHNEW_PARTYA = 114; // 老带新邀请奖励
    const INTEGRAL_OLDWITHNEW_PARTYB = 115; // 老带新被邀请
    const INTEGRAL_OLDWITHNEW_FIRSTPAY = 116; // 老带新首次消费
    const INTEGRAL_WORD_COUPON = 117; // 口令红包

    const BALANCE_VIP_GIVE = 200; // 会员赠送余额
    const BALANCE_ORDER_PAY = 201; // 余额支付
    const BALANCE_ORDER_REFUND = 202; // 余额支付退款
    const BALANCE_BUY = 203; // 充值
    const BALANCE_GIVE = 204; // 余额赠送
    const BALANCE_SIGNIN_GIVE = 205; // 签到赠送余额
    const BALANCE_GIFT_BIG = 206; // 新人礼包赠送
    const BALANCE_PAYGIFT = 207; // 支付有礼奖励余额撤回
    const BALANCE_PAYGIFT_REFUND = 208; // 支付有礼奖励余额撤回
    const BALANCE_ORDERCOLLECT = 209; //集点有礼奖励积分奖励
    const BALANCE_ORDERCOLLECT_REFUND = 210; // 集点有礼奖奖励积分撤回
    const BALANCE_EXCHANGECODE = 211; // 兑换活动
    const BALANCE_SIGNIN_CONTINUOUS = 212; //连续签到送余额
    const BALANCE_POINTS = 213; //积分兑换
    const BALANCE_BIRTHDAYGIFT_PERFECT = 214; // 完善资料余额奖励
    const BALANCE_BIRTHDAYGIFT_BIRTHDA = 215; // 生日有礼余额奖励
    const BALANCE_WORD_COUPON = 216; // 口令红包
    const BALANCE_WITHDRAWAL = 217; // 充值


    const CANWITHDRAWALAMOUNT_PARTNER = 31; //分销订单出账
    const CANWITHDRAWALAMOUNT_WITHDRAWAL = 32; //提现
    const CANWITHDRAWALAMOUNT_WITHDRAWAL_REF = 33; //提现驳回
    const CANWITHDRAWALAMOUNT_PARTNER_REWARD = 34; //分销奖励金

    const FREEZEAMOUNT_PARTNER_ADD = 41; //分销佣金预计收入
    const FREEZEAMOUNT_PARTNER_REFUND = 42; //分销佣金预计退还
    const FREEZEAMOUNT_PARTNER_BILL = 43; //分销佣金出账
    const FREEZEAMOUNT_REWARD_ADD = 44; //分销奖励金预计收入
    const FREEZEAMOUNT_REWARD_REFUND = 45; //分销奖励金退还

    const WITHDRAWAL = 51; //提现
    const WITHDRAWAL_OK = 52; //提现
    const WITHDRAWAL_ERROR = 53; //提现驳回
    const WITHDRAWALCOMPLETEAMOUNT = 61; //提现成功


    const EXP_VIP_GIVE = 700; // 会员赠送积分
    const EXP_ORDER_GIVE = 701; // 会员赠送积分
    const EXP_BUY = 701; // 会员充值赠送
    const EXP_STOREDVALUE_GIVE = 701; // 会员充值赠送

    protected $guarded = [];
    protected $appends = [
        'behaviorFormat'
    ];

    public function member()
    {
        return $this->hasOne(Member::class, 'id', 'userId')->select(['id', 'mobile', 'nickname']);
    }

    public function account()
    {
        return $this->hasOne(MemberAccount::class, 'userId', 'userId');
    }

    public function getBehaviorFormatAttribute()
    {
        $balance = [
            self::BASE => "系统调整",
            self::BALANCE_VIP_GIVE => "提升会员等级赠送余额",
            self::BALANCE_ORDER_PAY => "余额支付",
            self::BALANCE_ORDER_REFUND => "订单退款",
            self::BALANCE_BUY => "储值本金金额",
            self::BALANCE_GIVE => "储值赠送余额",
            self::BALANCE_GIFT_BIG => '新人礼包赠送余额',
            self::BALANCE_PAYGIFT => '支付有礼奖励余额', //
            self::BALANCE_PAYGIFT_REFUND => '支付有礼奖励余额撤回', //
            self::BALANCE_ORDERCOLLECT => '集点有礼奖励余额', //
            self::BALANCE_ORDERCOLLECT_REFUND => '集点有礼奖励余撤回',
            self::BALANCE_EXCHANGECODE => '兑换活动',
            self::BALANCE_SIGNIN_GIVE => '签到奖励余额',
            self::BALANCE_SIGNIN_CONTINUOUS => '连续签到奖励余额',
            self::BALANCE_POINTS => "积分商城兑换余额",
            self::BALANCE_BIRTHDAYGIFT_PERFECT => "完善资料余额奖励",
            self::BALANCE_BIRTHDAYGIFT_BIRTHDA =>  "生日有礼余额奖励",
            self::BALANCE_WORD_COUPON => "口令红包赠送余额",
            self::BALANCE_WITHDRAWAL => '分销提现',
            self::CANWITHDRAWALAMOUNT_PARTNER=> '分销佣金',
        ];
        $integral = [
            self::INTEGRAL_BUY_GIVE => "储值赠送积分",
            self::INTEGRAL_VIP_GIVE => "提升会员等级赠送积分",
            self::INTEGRAL_ORDER_GIVE => "订单赠送积分",
            self::INTEGRAL_GIFT_BIG => "新人礼包赠送积分",
            self::INTEGRAL_PAYGIFT => "支付有礼奖励积分",
            self::INTEGRAL_PAYGIFT_REFUND => '支付有礼奖励积分撤回',
            self::INTEGRAL_ORDERCOLLECT => "集点有礼奖励积分",
            self::INTEGRAL_ORDERCOLLECT_REFUND => ' 集点有礼奖励积分撤回',
            self::INTEGRAL_SIGNIN_GIVE => "签到奖励积分",
            self::INTEGRAL_SIGNIN_CONTINUOUS => "连续签到奖励积分",
            self::INTEGRAL_ORDER_PAY => "积分兑换",
            self::INTEGRAL_ORDER_REFUND => "积分退回",
            self::INTEGRAL_BIRTHDAYGIFT_PERFECT => "完善资料积分奖励", //
            self::INTEGRAL_BIRTHDAYGIFT_BIRTHDA => "生日有礼积分奖励", //
            self::INTEGRAL_OLDWITHNEW_PARTYA => "老带新邀请奖励", // 老带新邀请奖励
            self::INTEGRAL_OLDWITHNEW_PARTYB => '老带新被邀请', // 老带新被邀请
            self::INTEGRAL_OLDWITHNEW_FIRSTPAY => '老带新首次消费', // 老带新首次消费
            self::INTEGRAL_WORD_COUPON => "口令红包赠送积分"
        ];
        $exp = [
            self::EXP_VIP_GIVE => "提升会员等级赠送成长值",
            self::EXP_ORDER_GIVE => "订单赠送成长值",
            self::EXP_STOREDVALUE_GIVE => "储值赠送成长值"
        ];
        $data = $balance + $integral + $exp;
        return $data[$this->behavior];
    }

    public static function balanceList()
    {
        return  [
            self::BASE => 0,
            self::BALANCE_VIP_GIVE => 200, // 会员赠送余额
            self::BALANCE_ORDER_PAY => 201, // 余额支付
            self::BALANCE_ORDER_REFUND => 202, // 余额支付退款
            self::BALANCE_BUY => 203, // 充值
            self::BALANCE_GIVE => 204, // 余额赠送
            self::BALANCE_SIGNIN_GIVE => 205, // 签到赠送余额
            self::BALANCE_GIFT_BIG => 206 // 新人礼包赠送
        ];
    }

    public static function boot()
    {
        parent::boot();
        static::created(function ($member) {
            try {
                if ($member->cat == 'integral') {
                    $type = 'integralChange';
                }
                if ($member->cat == 'balance') {
                    $type = 'balanceChange';
                }
                Event(new MemberAccountEvent($member, $type));
            } catch (\Exception $e) {
                return true;
            }
        });
    }
}
