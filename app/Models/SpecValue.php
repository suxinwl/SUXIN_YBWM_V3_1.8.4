<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecValue extends Model
{
    protected $table = 'spec_value';
    use HasFactory;
    protected $fillable = ['uniacid', 'specId', 'name', 'img'];
    
}
