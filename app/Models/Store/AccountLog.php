<?php
namespace App\Models\Store;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
class AccountLog extends Model
{
    protected $table = 'store_account_log';
    use HasFactory;

    const CHANNEL_AMOUNT = 1; //余额
    const CHANNEL_WITHDRAWAL = 2;  //提现
    const CHANNEL_WITHDRAWAL_COMPLETE = 6;  //提现
    const CHANNEL_FREEZE = 3;  //冻结
    const CHANNEL_REFUND = 4;  //退款
    const CHANNEL_REFUNDOK = 5; //退款成功
    const COMMISSION_AMOUNT= 6;


    const AMOUNT_BASE = 11;   //系统调整
    const AMOUNT_PROFITSHARING = 12; // 分账
    const AMOUNT_ORDER_BILL = 13; //订单出账

    const WITHDRAWAL = 21;  //申请提现
    const WITHDRAWAL_PASS = 22;  //提现成功
    const WITHDRAWAL_REFUSE = 23;  //提现拒绝
    const WITHDRAWAL_CANCEL = 24;  //取消提现


    const FREEZE_ORDER_PAY = 31;  //订单支付
    const FREEZE_REFUND = 32;  //直接退款
    const FREEZE_BASE = 33;  //冻结
    const DISTRIBUTION_EXPENSES= 34;  //冻结
    const CANWITHDRAWALAMOUNT_PARTNER = 31; //分销订单出账
    const REFUND_BASE = 40;  //退款
    const REFUND_APPLY = 41;  //退款申请
    const REFUND_PASS = 42;  //退款成功
    const REFUND_DOWN = 43;  //拒绝退款

    const REFINDOK_OK = 51; //直接退款
    const REFINDOK_PASS = 52; //申请退款通过
    protected $guarded = [];
    protected $appends = [
        'format', 'orderTypeFormat'
    ];
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }

    public function getFormatAttribute()
    {
        return $this->format();
    }

    public function getOrderTypeFormatAttribute()
    {
        $data = [
            0 => "-",
            1 => '外卖/自提',
            2 => '储值订单',
            3 => '买单订单',
            4 => '店内订单'
        ];
        return $data[$this->orderType];
    }

    public function format()
    {
        $data = [
            self::AMOUNT_BASE => "系统调整",
            self::WITHDRAWAL => "申请提现",
            self::WITHDRAWAL_PASS => "提现成功",
            self::WITHDRAWAL_REFUSE => "提现驳回",
            self::WITHDRAWAL_CANCEL => "取消提现",
            self::FREEZE_REFUND => "订单退款",
            self::FREEZE_BASE => "账户冻结",
            self::REFUND_BASE => "退款",
            self::REFUND_APPLY => "申请退款",
            self::REFUND_PASS => "退款成功",
            self::REFUND_DOWN => "拒绝退款",
            self::REFINDOK_OK => "直接退款",
            self::REFINDOK_PASS => "申请退款通过",
            self::AMOUNT_PROFITSHARING => "服务费", //服务费
            self::AMOUNT_ORDER_BILL => "订单完结", //订单出账
            self::CANWITHDRAWALAMOUNT_PARTNER=> '分销佣金',
            self::DISTRIBUTION_EXPENSES=> '分销佣金支出',
        ];
        return $data[$this->behavior];
    }
}
