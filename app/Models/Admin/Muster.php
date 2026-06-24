<?php
namespace App\Models\Admin;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
class Muster extends BaseModel
{
    protected $table = 'muster';
    protected $primaryKey = 'id';
    /**
     * 类型转换
     *
     * @var array
     */
    protected $casts = [
        'plugStr' => 'array',
    ];

}
