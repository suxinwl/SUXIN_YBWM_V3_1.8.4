<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplyTop extends Model
{
    use HasFactory;
    protected $table = 'apply_top';
    protected $primaryKey = 'id';
    protected $fillable = ['adminId','uniacid'];
}
