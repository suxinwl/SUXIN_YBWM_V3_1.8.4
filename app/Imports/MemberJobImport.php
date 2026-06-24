<?php

namespace App\Imports;

use App\Jobs\MemberJob;
use App\Models\Store;
use App\Models\GoodsCat;
use App\Models\GoodsUnit;
use App\Models\GoodsSku;
use App\Models\GoodsSpu;
use App\Models\Material;
use App\Models\GoodsLabel;
use App\Models\Member;
use App\Models\Attr;
use App\Models\Member\Job;
use App\Models\Region;
use App\Models\StoreGroup;
use App\Models\StoreLabel;
use App\Models\Wechat\Kernel\Exceptions\Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Log;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

//use Maatwebsite\Excel\Concerns\ToModel;
//use Maatwebsite\Excel\Concerns\WithStartRow;

/*
 * 控制器调用导入
 * $a=Excel::import(new SpecsImport(2,1),$request->file('file'));
   echo json_encode(['code'=>1,'msg'=>'success']);die;
*/

class MemberJobImport implements ToArray
{
    private $uniacid;
    private $jobId;
    private $data;
    public function __construct($uniacid, $jobId)
    {
        $this->uniacid = $uniacid;
        $this->jobId = $jobId;
    }

    /**
     * @param array $row
     *
     * @return User|null
     */
    public function array(array  $rows)
    {
        $headers = array_shift($rows);
        $job = Job::find($this->jobId);
        try {
            $list =  collect($rows)->map(function ($item, $key) use ($headers, $job) {
                $line = $key + 1;
                $user = Member::where('uniacid', $this->uniacid)
                    ->where('storeId', $job->storeId)
                    ->where('mobile', $item[0])
                    ->first();
                if (empty($user)) {
                    throw new BadRequestException('手机号:' . $item[0] . '的用户不存在');
                }
                $job->memberCount = $job->memberCount + 1;
                return ['userId' => $user->id, 'changeType' => $item[1] > 0 ? 1 : 2, 'value' => abs($item[1])];
            })->toArray();
            $job->save();
            collect($list)->map(function ($user, $key) use ($job) {
                if ($job->type == 4) {
                    dispatch(new MemberJob($user['userId'], $job->id, $key + 1, $user['changeType'], $job->value));
                } else {
                    dispatch(new MemberJob($user['userId'], $job->id, $key + 1, $user['changeType'], $user['value']));
                }
            });
        } catch (\Exception $e) {
            DB::rollBack();
            return throw new BadRequestException($e->getMessage());
        }
    }
}
