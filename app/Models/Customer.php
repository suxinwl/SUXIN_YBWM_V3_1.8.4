<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class Customer extends BaseModel
{
    use HasFactory;
    protected $table = 'core_customer';

}
