<?php

namespace App\Models\OrderCollect;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends BaseModel
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'order_collect_user';

    protected $appends = [
        'point'
    ];
    public function getPointAttribute()
    {
        return $this->total - $this->issus;
    }
}
