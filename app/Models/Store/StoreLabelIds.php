<?php

namespace App\Models\Store;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLabelIds extends BaseModel
{

    protected $primaryKey = 'storeId';
    protected $table = 'store_label_ids';
    use HasFactory;
    protected $guarded = [];
}
