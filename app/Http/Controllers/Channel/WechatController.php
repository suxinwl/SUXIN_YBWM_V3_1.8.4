<?php

namespace App\Http\Controllers\Channel;

use App\Enums\WechatEnum;
use App\Models\WechatAttachment;
use App\Models\WechatMenu;
use App\Models\WechatReply;
use App\Services\ConfigService;
use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WechatController extends ApiController
{

    public function  materialAdd(Request $request)
    {
        $uniacid = $this->uniacid();
        $type = $request->header('type');
        $file = $request->file('file');
        $entension = $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();
        $filesize = $file->getSize();
        $newName = md5(date("Y-m-d H:i:s") . $fileName) . "." . $entension;
        if (!$file->isValid()) {
            return $this->failed("上传文件不合法");
        }
        $app = ChannelOpenWechat::officialAccount($uniacid);
        if ($type == 'image') {
            if (!in_array($entension, ['png', 'jpeg', 'gif', 'jpg', 'webp'])) {
                return $this->failed("允许图片上传的格式为:PNG\JPEG\JPG\GIF");
            }
            if (ceil($filesize / 1024) > 10240) {
                return $this->failed("上传图片大小超出限制:10Mb");
            }
            $path = $file->move(storage_path('/app/temp'), $newName);
            $mediaId = $app->material->uploadImage($path);
            @unlink($path);
            if (isset($mediaId['errcode'])) {
                throw new BadRequestException(WechatEnum::format($mediaId['errcode']));
            }
            WechatAttachment::create([
                'uniacid' => $uniacid,
                'file_name' => $fileName,
                'media_type' => $type,
                'media_id' => $mediaId['media_id'],
                'media_url' => $mediaId['url'],
                'description' => '',
                'is_temporary' => 'perm',
                'link_type' => 1
            ]);
            return $this->success($mediaId);
        }

        if ($type == 'voice') {
            if (!in_array($entension, ['mp3', 'wma', 'wma', 'wma'] ?: [])) {
                return $this->failed("允许音频文件上传的格式为:mp3/wma/wav/amr");
            }
            if (ceil($filesize / 1024) > 2048) {
                return $this->failed("上传音频文件大小超出限制:2Mb");
            }
            $path = $file->move(storage_path('/app/temp'), $newName);
            $mediaId = $app->material->uploadVoice($path);
            @unlink($path);
            WechatAttachment::create([
                'uniacid' => $uniacid,
                'file_name' => $fileName,
                'media_type' => $type,
                'media_id' => $mediaId['media_id'],
                'media_url' => '',
                'description' => '',
                'is_temporary' => 'perm',
                'link_type' => 1
            ]);
            return $this->success($mediaId);
        }

        if ($type == 'video') {
            if (!in_array($entension, ['mp4'] ?: [])) {
                return $this->failed("允许视频文件上传的格式为:MP4");
            }
            if (ceil($filesize / 1024) > 10240) {
                return $this->failed("上传视频文件大小超出限制:10Mb");
            }
            $path = $file->move(storage_path('/app/temp'), $newName);
            $mediaId = $app->material->uploadVideo($path, '视频', '视频');
            @unlink($path);
            WechatAttachment::create([
                'uniacid' => $uniacid,
                'file_name' => $fileName,
                'media_type' => $type,
                'media_id' => $mediaId['media_id'],
                'media_url' => '',
                'description' => '',
                'is_temporary' => 'perm',
                'link_type' => 1
            ]);
            return $this->success($mediaId);
        }

        return $this->failed('上传失败');
    }

    public function materialGet(Request $request)
    {
        $uniacid = $this->uniacid();
        $mediaId = $request->mediaId;  //"oGzymqfkGHMcHI3mVw2Hn_sJ-hcodA7fCouVvMk-bkel2r4hcIabstgT0LLoGGJV";
        $model = WechatAttachment::where('media_id', $mediaId)->first();
        if (empty($model)) {
            return $this->failed('资源不存在');
        }
        return $this->success($model);
    }


    public function menus()
    {
        $uniacid = $this->uniacid();
        $model = WechatMenu::where('uniacid', $uniacid)->first();
        $menu = empty($model) ? [] : $model->data;
        return $this->success($menu);
    }

    public function menuCreate(Request $request)
    {
        $uniacid = $this->uniacid();
        WechatMenu::where('uniacid', $uniacid)->delete();
        $model = WechatMenu::create([
            'uniacid' => $uniacid,
            'data' => $request->data,
        ]);
        if (empty($model)) {
            return $this->failed('目录创建失败');
        }
        $app = ChannelOpenWechat::officialAccount($uniacid);
        $current = $app->menu->create($model->wechatMenu());
        if ($current['errcode'] != 0) {
            throw new BadRequestException(WechatEnum::format($current['errcode']));
        }
        return $this->success([], '保存成功');
    }

    public function replyList(Request $request)
    {
        $list = WechatReply::where('uniacid', $this->uniacid())->orderBy('sort', 'asc')->paginate($request->pageSize ?? 20, '*', 'pageNo');
        return $this->success($list);
    }

    public function h5()
    {
        $data['url'] = URL::to('/h5?uniacid=' . $this->uniacid());
        $img = QrCode::format('png')->size(200)->generate($data['url']);
        $data['qrCode'] =  $code_url = 'data:image/png;base64,' . base64_encode($img);
        return $this->success($data);
    }

    public function checkName()
    {
        $data=ChannelOpenWechat::checkNickName($this->uniacid());
        if ($data['errcode'] == 0) {
            return $this->success($data);
        } else {
            return $this->failed($data['errmsg']);
        }
    }

}
