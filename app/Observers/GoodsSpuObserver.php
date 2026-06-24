<?php

namespace App\Observers;

use App\Models\Admin\Apply;
use App\Models\AdvertisingPass;
use App\Models\ChannelConfig;
use App\Models\GoodsSpu;
use App\Models\Member;
use App\Models\MemberAccount;
use App\Models\MemberBind;
use App\Models\ShopCategory;
use App\Models\SmsAccount;
use App\Services\ConfigService;
use App\Services\SmsAccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Request;

class GoodsSpuObserver
{
    /**
     * 监听数据创建后的事件。
     *
     * @param  User $user
     * @return void
     */
    public function created(GoodsSpu $apply)
    {
    }

    public function save(GoodsSpu $apply)
    {
    }


    public function deleting(GoodsSpu $goodsSpu)
    {
        if ($goodsSpu->specSwitch) {
            $goodsSpu->skus()->delete();
        } else {
            $goodsSpu->singleSpec()->delete();
        }
    }


    public function restoring(GoodsSpu $goodsSpu)
    {
        if ($goodsSpu->specSwitch) {
            $goodsSpu->skus()->withTrashed()->restore();
        } else {
            $goodsSpu->singleSpec()->withTrashed()->restore();
        }
    }

    public function forceDeleted(GoodsSpu $goodsSpu)
    {
        Log::error('spuorceDeleted');
        $goodsSpu->content()->delete();
        if ($goodsSpu->specSwitch) {
            $goodsSpu->skus()->withTrashed()->forceDelete();
        } else {
            $goodsSpu->singleSpec()->withTrashed()->forceDelete();
        }
    }
}
