<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends BaseModel
{
    use HasFactory;
    protected $table = 'core_district';

    protected $appends = [
        'value', 'label'
    ];
    protected $guarded = [];
    public function getLabelAttribute()
    {
        return $this->name;
    }
    public function getValueAttribute()
    {
        return $this->id;
    }


}
