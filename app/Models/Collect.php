<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collect extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'user_collect';
    protected $fillable = [
        'uniacid', 'type', 'collectId', 'userId'
    ];


    public function collect()
    {
        if ($this->type == 'store') {
            $class = Store::class;
        }
        return $this->hasOne($class, 'id', 'collectId');
    }
}
