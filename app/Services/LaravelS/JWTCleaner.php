<?php

namespace App\Services\LaravelS;

use Hhxsv5\LaravelS\Illuminate\Cleaners\JWTCleaner as BaseJWTCleaner;

class JWTCleaner extends BaseJWTCleaner
{
    protected $instances = [
        'tymon.jwt',
        'tymon.jwt.auth',
        'tymon.jwt.parser',
        'tymon.jwt.claim.factory',
        'tymon.jwt.payload.factory',
        'tymon.jwt.manager',
    ];
}
