<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Store;
class TiktokStoreList extends BaseModel
{
    use HasFactory;
    protected $table = 'tiktok_store_list';
    protected $guarded = [];
    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'storeId');
    }
}
