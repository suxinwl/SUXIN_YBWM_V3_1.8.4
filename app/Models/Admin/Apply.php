<?php

namespace App\Models\Admin;

use App\Models\ApplyPlugs;
use App\Models\BaseModel;
use App\Models\Member;
use App\Models\Drag;
use App\Models\PayConfig;
use App\Models\ChannelConfig;
use App\Models\Plug;
use App\Models\Region;
use App\Models\Setmeal;
use App\Models\SmsAccount;
use App\Models\StatisticsDay;
use App\Models\Store;
use App\Services\ConfigService;
use App\Services\DataSeederService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use App\Services\SmsAccountService;

class Apply extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'apply';
    protected $primaryKey = 'id';
    protected $fillable = ['deliveryChannel', 'payChange', 'storeNumInfinite', 'storeNum', 'applyName', 'applyImage', 'musterId', 'applyType', 'createCount', 'startTime', 'endTime', 'status', 'plugStr', 'timeType', 'createUserId', 'attachmentType', 'notes', 'plugType', 'sort', 'day', 'adminId', 'address', 'attachmentData', 'copyrightSwitch', 'copyright', 'type', 'smsSign'];
    protected $casts = [
        'plugStr' => 'array',
        'attachmentData' => 'array',
        'copyright' => 'array',
        'smsSign' => 'array',
        'address' => 'array',
        'deliveryChannel' => 'array'
    ];

    protected $attributes = [
        'copyrightSwitch' => 0,
        "attachmentType" => 0,
        'attachmentData' => '',
        'address' => '',
    ];

    protected $appends = [
        'typeFormat'
    ];

    public function getTypeFormatAttribute()
    {
        return $this->typeFormat();
    }



    //主管理员
    public function admin()
    {
        return $this->hasOne('App\Models\Admin', 'id', 'createUserId'); //这两种方法都是一样的使用
    }
    public function adminTop()
    {
        return $this->hasOne(ApplyTop::class, 'uniacid', 'id'); //这两种方法都是一样的使用
    }

    public function smsAccount()
    {
        return $this->hasOne(SmsAccount::class, 'uniacid', 'id'); //这两种方法都是一样的使用
    }


    public function typeFormat()
    {
        return $this->type == 1 ? "多门店" : "单门店";
    }
    /**
     * 所有操作员
     */
    public function operator()
    {
        return $this->hasMany('App\Models\Admin', 'uniacid', 'id');
    }

    public function muster()
    {
        return $this->hasOne(Setmeal::class, 'id', 'musterId');
    }

    public function plugs()
    {
        return $this->hasMany(ApplyPlugs::class, 'uniacid', 'id');
    }

    public function drag()
    {
        return $this->hasMany(Drag::class, 'uniacid', 'id');
    }


    public function payconfig()
    {
        return $this->hasMany(PayConfig::class, 'uniacid', 'id');
    }

    public function channelconfig()
    {
        return $this->hasMany(ChannelConfig::class, 'uniacid', 'id');
    }


















    public function refreshPlugs()
    {
        ApplyPlugs::where('uniacid', $this->id)->where('source', 1)->delete();
        $ids = $this->muster->package;
        $items = [];
        foreach ($ids as $key => $v) {
            $model = ApplyPlugs::where('uniacid', $this->id)->where('plugId', $v)->first();
            if (empty($model)) {
                $items[$key] = new ApplyPlugs([
                    "plugId" => $v,
                    'source' => 1,
                    "state" => 1,
                    "display" => 1,
                    'endTime' => $this->timeType == 2 ? $this->endTime : null
                ]);
            } else {
                $model->endTime = $this->endTime;
                $model->save();
            }
        }
        if ($items) {
            $res = $this->plugs()->saveMany($items);
            if (!$res) {
                throw new BadRequestException('更新插件失败');
            }
        }
        return true;
    }

    public function updatePlugs()
    {
        $ids = $this->muster->package;
        $items = [];
        foreach ($ids as $key => $v) {
            $model = ApplyPlugs::where('uniacid', $this->id)->where('plugId', $v)->first();
            if ($model) {
                $model->endTime = $this->timeType == 2 ? $this->endTime : null;
                $model->save();
            }
        }
        return true;
    }

    public function userChannel()
    {
        return  ApplyPlugs::with('plug')->whereHas('plug', function ($q) {
            $q->where("appType", 'channel');
            return $q;
        })->where('uniacid', $this->id)->get();
    }

    public function addressFormat()
    {
        if (!empty($this->address)) {
            $list =  Region::select('name')->whereIn('id', $this->address)->get();
            $list = collect($list)->pluck('name')->toarray();
        }
        return empty($list) ? '' : implode('/', $list);
    }

    public function store()
    {
        return $this->hasMany(Store::class, 'uniacid', 'id');
    }

    /**
     * 正常
     */
    public function scopeNormal($q)
    {
        return $q->where('status', 1)->where('musterId', '>', 0);
    }

    /**
     * 待审核
     */
    public function scopeAudit($q)
    {
        return $q->where('status', 6);
    }

    /**
     * 审核通过
     */
    public function scopePass($q)
    {
        return $q->where('status', 1)->where('musterId', 0);
    }

    /**
     * 审核驳回
     */
    public function scopeRejected($q)
    {
        return $q->where('status', 5);
    }


    /**
     * 黑名单
     */
    public function scopeBlack($q)
    {
        return $q->where('status', 2);
    }

    /**
     * 过期
     */
    public function scopeOverdue($q)
    {
        return $q->where('endTime', "<", date("Y-m-d H:i:s", time()));
    }

    public function MemberCount()
    {
        return Member::where("uniacid", $this->id)->count();
    }

    public function OrderCount()
    {
        return 0;
    }
    public function storeCount()
    {
        return Store::where("uniacid", $this->id)->count();
    }

    public function getCopyrightDataAttribute()
    {
        if ($this->copyrightSwitch == 1) {
            return $this->copyright;
        }
        if ($this->copyrightSwitch == 0) {
            $config = ConfigService::getSystemSet('copyrightSetting');
            if ($config->version->copyrightSwitch == 1) {
                return $config->version;
            }
        }
        return null;
    }


    public static function applyTotal()
    {
        return Apply::withTrashed()->count();
    }

    public static function boot()
    {
        parent::boot();
        static::created(function ($apply) {
            $data[] = ["h" => 0, 'uniacid' => $apply->id, 'day' => date("Y-m-d", time()), 'storeId' => 0];
            StatisticsDay::insert($data);
            $smsAccount = SmsAccount::create([
                'uniacid' => $apply->id,
                'count' => 0,
                'send_num' => 0
            ]);
            $smsNum = intval($apply->muster->smsNum);
            SmsAccountService::giving($apply->id, $smsNum, 0, "套餐赠送短信{$smsNum}条");
            DataSeederService::applyConfigSeed($apply->id);
            DataSeederService::applyDeliveryRuleSeed($apply->id);
            DataSeederService::applyVipSeed($apply->id);
            DataSeederService::dragSeed($apply->id);
        });
    }
}
