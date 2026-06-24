<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DragStorage extends Model
{
    protected $table = 'drag_storage';
    protected $fillable = ['name', 'logo', 'data'];
}
