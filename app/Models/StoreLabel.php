<?php

namespace App\Models;

use App\Models\Store\StoreLabelIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreLabel extends BaseModel
{
    protected $table = 'store_label';
    use HasFactory;
    protected $fillable = ['name', 'sort'];
    protected $withCount = ['store'];
    public function store()
    {
        return $this->hasMany(StoreLabelIds::class, 'labelId', 'id');
    }
}
