<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreGroup extends BaseModel
{
    protected $table = 'store_group';
    use HasFactory;
    protected $fillable = ['name', 'sort'];
    protected $withCount = ['store'];
    
    public function store()
    {
        return $this->hasMany(Store::class, 'groupId', 'id');
    }
}
