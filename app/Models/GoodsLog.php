<?php

namespace App\Models;

use App\Models\Order\OrderGoods;
use App\Models\Store\StoreBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\Admin;
class GoodsLog extends BaseModel
{
    protected $table = 'goods_log';
    use HasFactory;
    protected $fillable = ['uniacid', 'storeId', 'spuId', 'skuId', 'adminId', 'type'];
    protected $appends = [
        'typeFormat'
    ];
    public function skus()
    {
        return $this->hasMany(GoodsSku::class, 'spuId', 'spuId');
    }


    public function goods()
    {
        return $this->hasOne(GoodsSpu::class, 'id', 'spuId');
    }


    public function store(){
        return $this->hasOne(StoreBase::class,'id','storeId');
    }

    public function admin(){
        return $this->hasOne(Admin::class,'id','adminId');
    }

    public  static function setLog($uniacid,$storeId,$userId,$spuId,$skuId='',$type=1)
    {
        return GoodsLog::insert([
            'uniacid' => $uniacid,
            'storeId' => $storeId,
            'spuId' => $spuId,
            'skuId' => $skuId,
            'adminId'=>$userId,
            'type'=>$type,
            'created_at' => date("Y-m-d H:i:s", time()),
            'updated_at' => date("Y-m-d H:i:s", time())
        ]);
    }

    public function getTypeFormatAttribute()
    {
        $data = [
            1 => "添加",
            2 => "修改",
            3 => "上架",
            4 => "下架",
            5 => "软删除",
            6 => "恢复",
            7 => "删除",
        ];
        return $data[$this->type];
    }
}
