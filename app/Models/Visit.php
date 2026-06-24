<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Qcloud\Sms\SmsSingleSender;
use Mrgoon\AliSms\AliSms;
use Illuminate\Support\Facades\DB;
class Visit extends BaseModel{
    use HasFactory;
    protected $table = 'visit_list';
    protected $guarded = [];

    public static function visitCount($uniacid,$startTime,$endTime,$shopId=''){
        $query=Visit::where('uniacid',$uniacid)
            ->where('created_at', '>=', $startTime)->where('created_at', '<=', $endTime);
        if($shopId){
           $query=$query->where('shop_id',$shopId);
        }
        $count=$query->count();
        return $count;

    }

    public static  function scopeStateCount($query)
    {
        return $query->select(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d') as date,count(distinct userId) as visitUser,count(*) as visitCount"));
    }
}
