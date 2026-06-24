<?php

namespace App\Models\Member;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberLabelIds extends BaseModel
{
    protected $table = 'member_label_ids';
    use HasFactory;
}
