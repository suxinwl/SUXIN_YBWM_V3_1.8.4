<?php

namespace App\Models\OldWithNew;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends BaseModel
{
    use HasFactory;
    protected $table = 'old_with_new';
    protected $fillable = ['uniacid','storeId','state', 'newGiftSwitch', 'title', 'partyA', 'partyB', 'contents', 'startTime', 'endTime', 'partyAPage', 'partyBPage', 'shearPage'];
    protected $casts =  [
        'partyA' => 'array',
        'partyB' => 'array',
        'partyAPage' => 'array',
        'partyBPage' => 'array',
        'shearPage' => 'array'
    ];

    protected $appends = [
        'subState'
    ];
    public function getSubStateAttribute()
    {
        if (strtotime($this->endTime) > time()) {
            return 1;
        } else {
            return 0;
        }
    }
}
