<?php

namespace App\Models;

use App\Models\Member\MemberLabelIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberLabel extends BaseModel
{
    use HasFactory;
    protected $table = 'member_label';
    protected $fillable = ['sort','title','uniacid','storeId'];
    public function member()
    {
        return $this->hasMany(MemberLabelIds::class,'labelId','id');
    }
}
