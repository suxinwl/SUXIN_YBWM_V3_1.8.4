<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PointSign extends BaseModel
{
    protected $table = 'point_sign';
    protected $guarded = [];
    protected $casts =  [
        'coupons' => 'array',
        'continuous_data' => 'array'
    ];
    use HasFactory;
}
