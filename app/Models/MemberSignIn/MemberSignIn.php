<?php

namespace App\Models\MemberSignIn;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MemberSignIn extends Model
{
    use HasFactory;
    protected $table = 'member_signin';
    protected $fillable = ['storeId','uniacid', 'userId', 'first', 'last', 'total', 'continuous', 'max', 'couponCount', 'balance', 'integral'];

    public function member()
    {
        return $this->hasOne(Member::class, 'id', 'userId');
    }

    public function getIsLxAttribute()
    {
        return (date("d", time()) - date("d", strtotime($this->last ?? time()))) == 1;
    }


    public function getSignInLog($y = null, $m = null)
    {
        $y = $y ?? date("Y", time());
        $m = $m ?? date("m", time());
        $cro = Carbon::create($y, $m);
        $start = $cro->startOfMonth()->toDateString();
        $end = $cro->endOfMonth()->toDateString();
        $list = SignIn::where('uniacid', $this->uniacid)
            ->where('userId', $this->userId)
            ->where("day", ">=", $start)
            ->where('day', "<=", $end)
            ->get();
        return collect($list)->pluck('day')->all();
    }

    public function getTodayAttribute()
    {
        return SignIn::select(['id', 'day', 'userId'])->where('userId', $this->userId)->where("day", date("Y-m-d", time()))->first();
    }
}
