<?php

namespace App\Models\Drinks;

use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\Member\MemberBase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Store;

class LogBase extends BaseModel
{
    use HasFactory;
    protected $table = 'drinks_log';
}
