<?php

namespace App\Models;

use App\Models\Admin\Apply;
use App\Models\Member\MemberBase;
use App\Models\Store\Account;
use App\Services\ConfigService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class UserWithdrawal extends BaseModel
{
    protected $table = 'user_withdrawal';
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'userId',
        'withdrawalConfig',
        'rateConfig',
        'withdrawalMoney',
        'serviceMoney',
        'money',
        'state',
        'notes',
        'storeId',
        'uniacid',
        'lastTime',
        'channel'
    ];
    protected $casts =  [
        'withdrawalConfig' => 'array',
        'rateConfig' => 'array'
    ];
    protected $with = [
        'user'
    ];
    protected $appends = [
        'stateFormat'
    ];

    public function user()
    {
        return $this->hasOne(MemberBase::class, 'id', 'userId')->select(['id','nickname','mobile']);
    }

    public function apply()
    {
        return $this->hasOne(Apply::class, 'id', 'uniacid');
    }

    public function getStateFormatAttribute()
    {
        return $this->stateFormat();
    }

    public function scopeReview($query)
    {
        $query->where('state', 0);
    }

    public function scopePass($query)
    {
        $query->where('state', 1);
    }
    public function scopeReject($query)
    {
        $query->where('state', 2);
    }
    public function scopeCancel($query)
    {
        $query->where('state', 3);
    }

    public function stateFormat()
    {
        $data = [
            0 => "提现中",
            1 => "提现成功",
            2 => "已驳回",
            3 => "已取消"
        ];
        return $data[$this->state];
    }
    public function account()
    {
        return $this->hasOne(MemberAccount::class, 'userId', 'userId');
    }

    public function getWithdrawalConfig()
    {
        if (empty($this->account->withdrawalConfig)) {
            throw new BadRequestException('请先配置提现账户');
        }
        return $this->account->withdrawalConfig[$this->account->withdrawalConfig['default']];
    }


    public function getWithdrawalType()
    {
        if (empty($this->account->withdrawalConfig)) {
            throw new BadRequestException('请先配置提现账户');
        }
        return $this->account->withdrawalConfig['default'];
    }

    public function getRateConfig()
    {
        return collect(ConfigService::getChannelConfig('distributor', $this->uniacid))->toArray();
    }

    public function getLastOrder()
    {
        $config = $this->getRateConfig()['withdrawal'];
        $withdrawal_cycle = intval($config['period']);
        if ($withdrawal_cycle <= 0) {
            return true;
        }
        $startTime = date("Y-m-d H:i:s", time());
        $endTIme = date("Y-m-d H:i:s", strtotime("-{$withdrawal_cycle}day"));
        $order = UserWithdrawal::where('uniacid', $this->uniacid)->where(function ($q) use ($startTime, $endTIme) {
            $q->orWhere('state', 0)->orWhere(function ($q) use ($startTime, $endTIme) {
                return $q->whereBetween('lastTime', [$startTime, $endTIme])->where('state', 1);
            });
            return $q;
        })->first();
        return empty($order) ? true : false;
    }

    public function getServiceMoney()
    {
        $config = $this->getRateConfig()['withdrawal'] ?? null;
        if (empty($config)) {
            throw new BadRequestException('提现设置未配置');
        }
        if ($this->withdrawalMoney > $this->account->canWithdrawalAmount) {
            throw new BadRequestException('可提现金额不足');
        }

        if (!$this->getLastOrder()) {
            throw new BadRequestException('您还未到提现周期');
        }

        if ($config['rule'] == 2) {
            throw new BadRequestException('提现未开启');
        }
        $rate = $config['rate'];
        $minMoney =  $config['money'];
        $min = $config['min'];
        $max = $config['max'];
        if ($this->withdrawalMoney < $minMoney) {
            throw new BadRequestException('最低提现金额:' . $minMoney);
        }
        $money  = bcmul(bcdiv($this->withdrawalMoney, 1000, 4), $rate * 10, 2);
        if ($money < $min) {
            return $min;
        }
        if ($money > $max) {
            return $max;
        }
        return $money;
    }

    public function getMoney()
    {
        $serviceMoney = $this->getServiceMoney();
        return bcsub($this->withdrawalMoney, $serviceMoney, 2);
    }
}
