<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Config extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'config';
    protected $fillable = [
        'data', 'identName', "ident", "uniacid"
    ];

    /**
     * 查询用户的时候name字段处理
     *
     * @author Eric
     * @param $value
     * @return string
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * 添加用户的时候name字段处理
     *
     * @author Eric
     * @param $value
     * @return string
     */
    // public function setDataAttribute($value)
    // {
    //     return json_encode($value,320);
    // }

    public static  function getSystemSet($ident, $uniacid = 0)
    {
        $res = self::where('ident', $ident)->where('uniacid', $uniacid)->first();
        if ($res) {
            $data = $res->data ?: [];
            $data->ident = $ident;
            $data->identName = $res->identName;
            if($ident =='merchantMini'){
                $json = Storage::disk('local')->get('merchant/version.json');
                $json = json_decode($json,true);
                $data->version =  $json['version'];
            }
        } else {
            $data = [];
        }
        return  $data;
    }

    public static function saveSystemSet($config, $ident, $uniacid = 0, $identName)
    {
        $res = self::where('ident', $ident)->where('uniacid', $uniacid)->first();
        if ($res) {
            $model = Config::find($res->id);
            $model->data = json_encode($config);
            $model->ident = $ident;
            $model->identName = $identName;
            $model->uniacid = $uniacid;
            $model->save();
        } else {
            $model = new Config();
            $model->data = json_encode($config);
            $model->ident = $ident;
            $model->identName = $identName;
            $model->uniacid = $uniacid;
            $model->save();
        }
        return true;
    }


    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $key =  "sysConfigMap:";
            Cache::delete($key);
        });
    }

    // public static function plugLang($list)
    // {
    //     $data = [
    //         1 => [
    //             'label' => '1',
    //             'value' => '1'
    //         ],
    //         2 => [
    //             'label' => '2',
    //             'value' => '2'
    //         ],
    //         'mini' => [
    //             'label' => '微信小程序',
    //             'value' => 'mini'
    //         ],
    //         'ali' => [
    //             'label' => '支付宝小程序',
    //             'value' => 'ali'
    //         ],
    //         'zijie' => [
    //             'label' => '字节跳动小程序',
    //             'value' => 'zijie'
    //         ],
    //         'wechat' => [
    //             'label' => '微信公众号',
    //             'value' => 'wechat'
    //         ],
    //         'miniPlay' => [
    //             'label' => '小程序直播',
    //             'value' => 'miniPlay'
    //         ],
    //         'rollBag' => [
    //             'label' => '小程序直播',
    //             'value' => 'rollBag'
    //         ],
    //         'payVip' => [
    //             'label' => '付费会员卡',
    //             'value' => 'payVip'
    //         ],
    //         'cashier' => [
    //             'label' => '收银台',
    //             'value' => 'cashier'
    //         ],
    //         'oldWithNew' => [
    //             'label' => '老带新',
    //             'value' => 'oldWithNew'
    //         ],
    //         'distribution' => [
    //             'label' => '分销商',
    //             'value' => 'distribution'
    //         ],
    //         'queuing' => [
    //             'label' => '排队取号',
    //             'value' => 'queuing'
    //         ],
    //         'reserve' => [
    //             'label' => '餐桌预定',
    //             'value' => 'reserve'
    //         ],
    //         'dividend' => [
    //             'label' => '瓜分红包',
    //             'value' => 'dividend'
    //         ],
    //         'tiktokCode' => [
    //             'label' => '抖音口令',
    //             'value' => 'tiktokCode'
    //         ],
    //     ];
    //     $lang = [];
    //     foreach ($list as $key => $v) {

    //         if (isset($data[$v])) {
    //             $lang[] = $data[$v];
    //         } else {
    //             $lang[] = ['label' => $v, 'value' => $v];
    //         }
    //     }
    //     return $lang;
    // }

}
