<?php

namespace App\Models\Member;

use App\Models\BaseModel;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends BaseModel
{
    protected $table = 'member_group';
    use HasFactory;
    protected $fillable = ['sort', 'uniacid', 'name','storeId'];

    public function member()
    {
        return $this->hasOne(Member::class, 'groupId', 'id');
    }
}
