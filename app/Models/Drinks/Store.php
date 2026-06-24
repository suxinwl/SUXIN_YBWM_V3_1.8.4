<?php

namespace App\Models\Drinks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;
    protected $table = 'drinks_store';
    protected $fillable = [
        'uniacid','drinksId','storeId'
    ];
}
