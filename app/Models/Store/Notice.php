<?php

namespace App\Models\Store;

use App\Models\BaseModel;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends BaseModel
{
    protected $table = 'store_notice';
    use HasFactory;
    protected $fillable = ['storeId', 'uniacid', 'title', 'contents', 'sort', 'type', 'startTime', 'endTime', 'state'];

    protected $casts =  [
        'storeId' => 'array',
    ];
    public function stores()
    {
        return $this->belongsToMany(StoreBase::class, 'store_notice_ids', 'noticeId', 'storeId');
    }
}
