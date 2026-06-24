<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminGroup extends BaseModel
{
    protected $table = 'admin_group';
    protected $guarded = [];
    use HasFactory;

    public  function admins()
    {
        return $this->hasMany('App\Models\Admin', 'group_id', 'id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'service');
    }

}
