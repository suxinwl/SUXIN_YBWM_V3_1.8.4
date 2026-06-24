<?php

namespace App\Traits\Admin;

use App\Models\Store;
use Illuminate\Support\Facades\DB;

trait InitTrait
{
    public function user()
    {
        return auth('admin')->user();
    }

    public function userId()
    {
        return $this->user() ?  $this->user()->id : 0;
    }
}
