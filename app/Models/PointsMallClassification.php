<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PointsMallClassification extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'points_mall_classification';
    protected $guarded = [];
}
