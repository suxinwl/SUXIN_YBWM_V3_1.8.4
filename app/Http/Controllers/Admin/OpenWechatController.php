<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ApiController;
use App\Models\OpenWecahtExtJson;
use App\Models\OpenWecahtVersion;
use App\Models\OpenWechat;
use App\Models\OpenWechatAuth;
use App\Services\ConfigService;
use App\Services\OpenWechat\AdminOpenWechat;
use Illuminate\Http\Request;

class OpenWechatController extends ApiController
{
    /**
     * 草稿箱
     */
    public function draftbox()
    {
        $app = AdminOpenWechat::openPlatform();
        $res = $app->code_template->getDrafts();
        if ($res['errcode'] == 0) {
            $res['draft_list'] = collect($res['draft_list'])->sortByDesc('create_time')->all();
            foreach ($res['draft_list'] as $key => $v) {
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                $data[] = $v;
            }
            return $this->success($data);
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 代码库列表
     */
    public function templateList()
    {
        $app = AdminOpenWechat::openPlatform();
        $res  = $app->code_template->list();
        $model = OpenWecahtVersion::first();
        if ($res['errcode'] == 0) {
            $res['template_list'] =  collect($res['template_list'])->sortByDesc('create_time')->toArray();
            foreach ($res['template_list'] as $key => $v) {
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                $v['release_time'] = null;
                $v['release_state'] = 0;
                if ($v['template_id'] == $model->template_id) {
                    $v['release_time'] = $model->release_time;
                    $v['release_state'] = 1;
                }
                $data[] = $v;
            }
            return $this->success($data);
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 草稿转为模板
     */
    public function templateSelect(Request $request)
    {
        $app = AdminOpenWechat::openPlatform();
        $res =  $app->code_template->createFromDraft($request->draft_id, 0);
        if ($res['errcode'] == 0) {
            return $this->success([]);
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 删除模板
     */

    public function templateDelete()
    {
        $app = AdminOpenWechat::openPlatform();
        $res = $app->code_template->delete(Request()->templateId);
        if ($res['errcode'] == 0) {
            $model = OpenWecahtVersion::where('template_id', Request()->templateId)->delete();
            return $this->success([]);
        }
        return $this->failed($res['errmsg']);
    }

    /**
     * 发布版本
     */
    public function release(Request $request)
    {
        $app = AdminOpenWechat::openPlatform();
        $res  = $app->code_template->list();
        if ($res['errcode'] != 0) {
            return $this->failed($res['errmsg']);
        }

        foreach ($res['template_list'] as $key => $v) {
            if ($v['template_id'] == $request->templateId) {
                $model = OpenWecahtVersion::first();
                if ($model) {
                    $model->delete();
                }
                $extJson = OpenWecahtExtJson::where('version', $v['user_version'])->first();
                if (empty($extJson)) {
                    return $this->failed('当前版本缺少ext.json,请重新上传小程序再发布版本');
                }
                $model = new OpenWecahtVersion();
                $model->version = $v['user_version'];
                $model->desc = $v['user_desc'];
                $model->template_id = $v['template_id'];
                $model->extJson  = $extJson->extJson;
                $model->created_at = date("Y-m-d H:i:s", $v['create_time']);
                $model->release_time = date("Y-m-d H:i:s", time());
                $model->type = 0;
                $model->save();
                return $this->success(null);
            }
        }
    }

    public function  check()
    {
        try {
            $res = AdminOpenWechat::getAuthorizationUrl(url('common/openWechat/auth/1'), 1);
            return $this->success($res, '检测成功');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
