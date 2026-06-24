<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminBind extends BaseModel
{
    use HasFactory;
    protected $table = 'admin_bind';
    public function Admin()
    {
        return $this->hasOne('App\Models\Admin', 'id', 'userId');
    }
    protected $casts =  [
        'data' => 'array',
    ];
}
