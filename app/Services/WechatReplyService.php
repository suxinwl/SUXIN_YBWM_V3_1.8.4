<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\WechatReply;
use Illuminate\Support\Facades\Cache;
use Overtrue\EasySms\EasySms;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WechatReplyService extends BaseService
{
    const TYPE_TEXT = "text";
    const TYPE_IMAGE = "image";
    const TYPE_VIDEO = "video";
    const TYPE_VOICE = "Voice";
    const TYPE_NEWS = "news";
    const TYPE_MINI = "mini";

    public function getMessage($id, $openId)
    {
        $message = WechatReply::find($id);
        if (empty($message)) {
            return false;
        }
        switch ($message->type) {
            case self::TYPE_TEXT:
                break;
            case self::TYPE_IMAGE:
                break;
            case self::TYPE_VIDEO:
                break;
            case self::TYPE_VOICE:
                break;
            case self::TYPE_NEWS:
                break;
            case self::TYPE_MINI:
                break;
        }
    }
}
