<?php

namespace App\Models\Mini;

use App\Services\OpenWechat\ChannelOpenWechat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApplyMiniPath extends Model
{
    protected $table = 'apply_mini_path';
    protected $fillable = ['uniacid','type','channel'];
    use HasFactory;
    public function miniPath()
    {
        return $this->hasOne(MiniPath::class, 'type', 'type');
    }

    public function clear()
    {
        if ($this->channel == 1) {
            $app = ChannelOpenWechat::miniProgram($this->uniacid);
            $res = $app->qr_code->delete($this->miniPath->url);
            if ($res['errcode'] != 0) {
                throw new BadRequestHttpException($res['errmsg']);
            }
        }
        return true;
    }

    public function add()
    {
        if ($this->channel == 1) {
            $app = ChannelOpenWechat::miniProgram($this->uniacid);
            $res = $app->qr_code->getVerifyFile();
            if ($res['errcode'] != 0) {
                throw new BadRequestHttpException($res['errmsg']);
            }
            $file = Storage::disk('index')->put($res['file_name'], $res['file_content']);
            $params = [
                'prefix' => $this->miniPath->url,
                "path" => $this->miniPath->path,
                'open_version' => 3,
                'permit_sub_rule' => 1
            ];
            $res = $app->qr_code->set($params);
            if ($res['errcode'] != 0) {
                throw new BadRequestHttpException($res['errmsg']);
            }
            $res = $app->qr_code->publish($this->miniPath->url);
            if ($res['errcode'] != 0) {
                throw new BadRequestHttpException($res['errmsg']);
            }
        }
        return true;
    }
}
