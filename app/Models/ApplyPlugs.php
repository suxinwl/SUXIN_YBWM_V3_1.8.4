<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplyPlugs extends Model
{
    protected $table = 'apply_plugs';
    use HasFactory;
    protected $guarded = [];

    public function plug()
    {
        return $this->hasOne(Plug::class, 'id', 'plugId');
    }
}
