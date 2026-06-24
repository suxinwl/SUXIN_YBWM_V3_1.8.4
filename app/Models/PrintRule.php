<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
class PrintRule extends Model
{
    protected $table = 'print_rule';
    protected $fillable = ['name', 'uniacid', 'storeId', 'printId ', 'type', 'scene', 'config', 'md5Str','printer_size'];
    protected $casts =  [
         'config'=>'array',
         'scene'=>'array',
    ];
    use HasFactory;
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
