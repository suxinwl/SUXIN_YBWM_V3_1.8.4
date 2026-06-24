<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class BrowseCircle extends BaseModel
{
    protected $table = 'browse_circle';
    protected $guarded = [];

    use SoftDeletes;// 开启软删除
    use HasFactory;

}
