<?php

namespace App\Models\GoodsRecommend;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recommend extends BaseModel
{
    use HasFactory;
    protected $table = 'goods_recommend';
    protected $fillable = ['name', 'desc','type'];

    protected $attributes = [
        'type' => 1
    ];
    public function goods()
    {
        return $this->hasMany(Goods::class, 'recommendId', 'id');
    }

    public function store()
    {
        return $this->hasMany(Store::class, 'recommendId', 'id');
    }
}
