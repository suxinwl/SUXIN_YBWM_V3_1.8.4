<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class HandleLog extends BaseModel
{
    use HasFactory;
    protected $table = 'handle_log';
    protected $primaryKey = 'id';


    protected $attributes = [
        "uniacid" => 0
    ];
    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'userId');
    }
}
