<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberBind extends BaseModel
{
    protected $table = 'member_bind';
    use HasFactory;
    protected $guarded = [];
    public function Member()
    {
        return $this->hasOne('App\Models\Member', 'id', 'userId');
    }
}
