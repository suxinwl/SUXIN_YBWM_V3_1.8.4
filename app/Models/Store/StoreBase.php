<?php

namespace App\Models\Store;

use App\Enums\SceneEnum;
use App\Models\BaseModel;
use App\Models\FullSub\FullSub;
use App\Models\GoodsRecommend\Store as GoodsRecommendStore;
use App\Models\NewSub\NewSub;
use App\Models\Recipe\RecipeStore;
use App\Models\Store\Account;
use App\Models\Store\Notice;
use App\Models\StoreConfig;
use App\Models\TakeOut\Delivery;
use App\Services\ConfigService;
use App\Services\DataSeederService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;

class StoreBase extends BaseModel
{
    protected $table = 'store';
    use HasFactory, SoftDeletes;
}
