<?php

namespace App\Services;

use App\Models\ApplyPlugs;
use App\Models\Plug;
use App\Models\Tables\ServersIds;
use App\Traits\ResourceTrait;

class TableService
{
    public static function tableMoney($tableId, $man = 0)
    {
        $serverIds = ServersIds::where('tableId', $tableId)->first();
        if (!$serverIds || !$serverIds->tableServer) {
            return ['tableMoney' => 0, 'tableNum' => 0, 'tableFormat' => null];
        }
        if ($serverIds->tableServer->type == 1) {
            return  ['tableMoney' => bcmul(floatval($serverIds->tableServer->price ?? 0), intval($man), 2), 'tableNum' => intval($man), 'tableFormat' => $serverIds->tableServer->name];
        } else {
            return  ['tableMoney' => bcmul(floatval($serverIds->tableServer->price ?? 0), intval($man), 2), 'tableNum' => 1, 'tableFormat' => $serverIds->tableServer->name];
        }
    }
}
