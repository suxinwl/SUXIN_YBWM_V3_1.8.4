<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PointsMall extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'points_mall';
    protected $guarded = [];
    protected $casts =  [
        'coupon_collection' => 'array',
        'deliveryChannel' => 'array',
        'storeIds' => 'array',
    ];

    public function Category(){
        return $this->hasOne(PointsMallClassification::class,'id','type_id');
    }
}
