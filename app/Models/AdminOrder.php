<?php

namespace App\Models;

use App\Models\Admin\Apply;
use App\Services\SmsAccountService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AdminOrder extends Model
{
    protected $table = 'admin_pay_order';
    protected $guarded = [];
    use HasFactory;
    protected $casts =  [
        'attach' => 'array',
    ];

    public function createApply()
    {
        $attach = $this->attach;
        $apply_model = Apply::find($this->applyId);
        if ($apply_model) {
            $apply_model->musterId = $this->goodsId;
            $master = Setmeal::find($this->goodsId);
            $apply_model->startTime = date("Y-m-d H:i:s",time());
            $apply_model->endTime =date("Y-m-d H:i:s",time() + $this->day * 86400);
            $apply_model->storeNumInfinite = 0;
            $apply_model->storeNum =  $master->storeNum;
            $apply_model->timeType = 2;
            Log::error("storeNum:".$master->storeNum);
            if ($apply_model->save()) {
                $smsNum = intval($master->smsNum);
                SmsAccountService::giving($apply_model->id, $smsNum, 0, "套餐赠送短信{$smsNum}条");
                $apply_model->refreshPlugs();
                return true;
            }
        }
        return false;
    }

    public function apply()
    {
        return $this->hasOne(Apply::class, 'id', 'applyId');
    }
}
