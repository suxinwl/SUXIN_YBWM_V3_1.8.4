<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatReply extends BaseModel
{
    use HasFactory;
    protected $table = 'wechat_reply';
    protected $guarded = [
        'id' // 'data', 'type', 'channel', 'title', 'keyword', 'rules', 'sort', 'sort'
    ];
    protected $casts =  [
        'data' => 'array',
    ];

    protected $attributes = [
        'sort' => 0,
        'title' => '',
        'type' => 'text',
        'keyword' => '',
        'rules' => 0,
        'state' => 1
    ];

    const TYPE_TEXT = "text";
    const TYPE_IMAGE = "image";
    const TYPE_VIDEO = "video";
    const TYPE_VOICE = "Voice";
    const TYPE_NEWS = "news";
    const TYPE_MINI = "mini";


    public function getMessage($openId)
    {
        $data = [];
        foreach ($this->data as $key => $reply) {
            switch ($reply['type']) {
                case self::TYPE_TEXT:
                    array_push($data, [
                        "touser" => $openId,
                        "msgtype" => 'text',
                        "text" => [
                            "content" => $reply['content']
                        ]
                    ]);
                    break;
                case self::TYPE_IMAGE:
                    array_push($data, [
                        "touser" => $openId,
                        "msgtype" => 'image',
                        "image" => [
                            "media_id" => $reply['media_id']
                        ]
                    ]);
                    break;
                case self::TYPE_VIDEO:
                    array_push($data, [
                        "touser" => $openId,
                        "msgtype" => 'video',
                        "video" => [
                            "media_id" => $reply['media_id'],
                            "thumb_media_id" => $reply['thumb_media_id'],
                            "title" => $reply['title'],
                            "description" => $reply['description']
                        ]
                    ]);
                    break;
                case self::TYPE_VOICE:
                    array_push($data, [
                        "touser" => $openId,
                        "msgtype" => 'video',
                        "video" => [
                            "media_id" => $reply['media_id']
                        ]
                    ]);
                    break;
                case self::TYPE_NEWS:
                    array_push($data, [
                        "touser" => $openId,
                        "msgtype" => 'news',
                        "news" => [
                            "articles" => [
                                [
                                    'title' => $reply['title'],
                                    "url" => $reply['url'],
                                    "picurl" => $reply['picurl'],
                                    "description" => $reply['description']
                                ]
                            ]
                        ]
                    ]);
                    break;
                case self::TYPE_MINI:
                    array_push($data,  [
                        "touser" => $openId,
                        "msgtype" => 'miniprogrampage',
                        "miniprogrampage" => [
                            'title' => $reply['title'],
                            'appid' => $reply['appid'],
                            'pagepath' => $reply['pagepath'],
                            'thumb_media_id' => $reply['thumb_media_id'] ?? $reply['media_id']
                        ]
                    ]);
                    break;
                default:
                    break;
            }
        }
        return $data;
    }
}
