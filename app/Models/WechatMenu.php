<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatMenu extends BaseModel
{
    use HasFactory;
    protected $table = 'wechat_menu';
    protected $guarded = [];
    protected $casts =  [
        'data' => 'array',
    ];

    const TYPE_CLICK = 1; //点击事件
    const TYPE_VIEW = 2; //跳转链接
    const TYPE_MINI = 3; //跳转小程序
    const TYPE_IMAGE = 4; //发送图片;
    const TYPE_MP3 = 5; //发送音频;
    const TYPE_VIDEO = 6; //发送视频
    const TYPE_KEYWORD = 7; //发送关键字
    const TYPE_TEXT = 8; //发送关键字


    public function wechatMenu()
    {
        $data = [];
        foreach ($this->data as $key => $v) {
            if (empty($v['sub'])) {
                $temp = $this->menuData($v);
                $temp['sub_button'] = [];
            } else {
                $temp = $this->menuData($v);
                foreach ($v['sub'] as $key2 => $v2) {
                    $temp['sub_button'][$key2] = $this->menuData($v2);
                }
            }
            $data[$key] = $temp;
        }
        return $data;
    }

    private function menuData($v)
    {
        $type = $v['pType'];
        switch ($type) {
            case  self::TYPE_CLICK:
                return [
                    'type' => 'click',
                    'name' => $v['name'],
                    'key' => $v['key']
                ];
                break;
            case  self::TYPE_VIEW:
                return [
                    'type' => 'view',
                    'name' => $v['name'],
                    'url' => $v['url']
                ];
                break;
            case  self::TYPE_MINI:
                return [
                    'type' => 'miniprogram',
                    'name' => $v['name'],
                    'url' => $v['url'],
                    "appid" => $v['appId'],
                    "pagepath" => $v['pagepath']
                ];
                break;
            case  self::TYPE_MP3:
                return [
                    'type' => 'media_id',
                    'name' => $v['name'],
                    'media_id' => $v['key']
                ];
                break;
            case  self::TYPE_IMAGE:
                return [
                    'type' => 'media_id',
                    'name' => $v['name'],
                    'media_id' => $v['key']
                ];
                break;
            case  self::TYPE_VIDEO:
                return [
                    'type' => 'media_id',
                    'name' => $v['name'],
                    'media_id' => $v['key']
                ];
                break;
            case  self::TYPE_KEYWORD:
                return [
                    'type' => 'click',
                    'name' => $v['name'],
                    'key' => $v['key']
                ];
                break;
            case  self::TYPE_TEXT:
                return [
                    'type' => 'click',
                    'name' => $v['name'],
                    'key' => $v['key']
                ];
                break;
            default:
                return [
                    'name' => $v['name'],
                ];
                break;
        }
        return [];
    }
}
