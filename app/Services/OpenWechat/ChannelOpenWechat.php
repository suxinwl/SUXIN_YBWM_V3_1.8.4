<?php

namespace App\Services\OpenWechat;

use App\Models\Config;
use App\Models\MiniVersion;
use App\Models\OpenWecahtVersion;
use App\Models\OpenWechatAuth;
use App\Services\ConfigService;
use EasyWeChat\Factory;
use App\Services\BaseService;
use App\Services\OpenWechat\AdminOpenWechat as OpenWechatAdminOpenWechat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ChannelOpenWechat extends OpenWechatAdminOpenWechat
{

    /**
     * 获取当前小程序版本信息
     */
    public static function getMiniVersion($uniacid, $commit = false)
    {
        $config = self::getConfig($uniacid);
        $version = self::getNewVersion($uniacid);
        $model = MiniVersion::where('appid', $config->authorizer_appid)
            ->where(function ($q) use ($commit) {
                if ($commit) {
                    return $q->whereIn('state', [1, 2, 3, 4, 5]);
                }
                return $q;
            })->orderBy('id', 'desc')->orderBy('state', 'asc')->first();
        return $model;
    }


    /**
     * 获取发布版本小程序
     */
    public static function getNewVersion($uniacid)
    {
        $version = OpenWecahtVersion::first();
        if (empty($version)) {
            throw new BadRequestHttpException('当前平台没有已发布的版本,请联系管理员');
        }
        return $version;
    }

    /**
     * 判断小程序是否需要更新
     */
    public static function isUpload($uniacid)
    {
        $miniVersion = self::getMiniVersion($uniacid);
        $version = self::getNewVersion($uniacid);
        if ($miniVersion) {
            if ($miniVersion->template_id == $version->template_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取小程序配置
     */
    public static function getConfig($uniacid, $type = 'mini', $throw = true)
    {
        $model = OpenWechatAuth::where('uniacid', $uniacid)->where('type', $type)->first();
        if (empty($model) && $throw) {
            throw new BadRequestHttpException('微信小程序未授权');
        }
        return $model;
    }

    /**
     * 获取小程序实例
     */
    public static function miniProgram($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $app = self::openPlatform();
        return $app->miniProgram($model->authorizer_appid, $model->authorizer_refresh_token);
    }

    /**
     * 获取公众号实例
     */
    public static function officialAccount($uniacid)
    {
        $model = self::getConfig($uniacid, 'official');
        $app = self::openPlatform();
        return $app->officialAccount($model->authorizer_appid, $model->authorizer_refresh_token);
    }

    /**
     * 设置小程序服务器域名
     */
    public static function setDomain($uniacid, $domain, $actio = 'get')
    {
        $app = self::miniProgram($uniacid);
        return $app->domain->setWebviewDomain($domain, 'get');
    }

    /**
     * 设置小程序业务域名
     */
    public static function webviewdomain($uniacid, $domain = [], $action = 'get')
    {
        $app = self::miniProgram($uniacid);
        return $app->domain->setWebviewDomain($domain, 'set');
    }


    /**
     * 上传代码
     */
    public static function commit($uniacid, $isNew = true)
    {
        $app = self::miniProgram($uniacid);
        if (!self::isUpload($uniacid)) {
            return ['errcode' => 0, 'errMsg' => 'ok'];
        }
        $version = self::getNewVersion($uniacid);
        $extJson = json_decode($version['extJson'], true);
        $extJson['extAppid'] = $app->config['app_id'];
        $extJson['ext']['uniacid'] = $uniacid;
        $extJson['ext']['host'] = "https://" . Request()->getHttpHost();
        $extJson['ext']['siteroot'] = "https://" . Request()->getHttpHost();
        $extJson['plugins'] = (object)[];
        $res = $app->code->commit($version['template_id'], json_encode($extJson), $version['version'], $version['desc']);
        if ($res['code'] == 0 && $isNew) {
            $model = MiniVersion::where('appid', $app->config['app_id'])->where('version', $version['version'])->first();
            if (empty($model)) {
                $model = new MiniVersion();
            }
            $model->version = $version['version'];
            $model->appid = $app->config['app_id'];
            $model->template_id = $version['template_id'];
            $model->desc = $version['desc'];
            $model->state = 5;
            $model->commit_time = date("Y-m-d H:i:s");
            $model->audit_time = null;
            $model->audit_ok_time = null;
            $model->reason = null;
            $model->release_time = null;
            $model->screenshot = null;
            $model->auditid = null;
            $model->auditid = 0;
            $model->save();
        }
        return $res;
    }

    /**
     * 提交审核
     */
    public static function submitAudit($uniacid, $autoRelease = false)
    {
        $version = self::getMiniVersion($uniacid);
        if ($version && in_array($version->state, [2, 4])) {
            throw new BadRequestHttpException('版本正在审核中');
        }
        $app = self::miniProgram($uniacid);
        $commit = static::commit($uniacid);
        if ($commit['errcode'] != 0) {
            throw new BadRequestHttpException($commit['errmsg']);
        }
        sleep(1);
        $res = $app->code->submitAudit([
            'item_list' => [],
            'ugc_declare' => [
                'scene' => [4],
                'method' => [1, 3],
                'audit_desc' => '目前用户发帖处采用了内容安全API并后采用了先审再发机制，日常也会配合人工审核检查，发现用词不当或令人不适的图片会及时删除',
            ],
            'order_path' => 'pages/index/order-index'
        ]);
        if ($res['errcode'] == 0) {
            $model = self::getMiniVersion($uniacid);
            $model->audit_time = date("Y-m-d H:i:s", time());
            $model->state = 2;
            $model->autoRelease = 1;
            $model->auditid = $res['auditid'];
            $model->save();
            return $res;
        }
        return $res;
    }

    /**
     * 撤回审核
     */
    public static function undocodeaudit($uniacid)
    {
        $app = self::miniProgram($uniacid);
        $res = $app->code->withdrawAudit();
        if ($res['errcode'] == 0) {
            $model = self::getMiniVersion($uniacid);
            $model->state = 3;
            $model->save();
            return $res;
        }
        return $res;
    }

    public static function speedupCodeAudit($uniacid)
    {
        $app = self::miniProgram($uniacid);
        $model = self::getMiniVersion($uniacid, true);
        if ($model->state != 2) {
            throw new BadRequestHttpException('当前版本不是审核中');
        }
        $res = $app->code->speedupAudit($model->auditid);
        return $res;
    }

    /**
     * 获取体验版二维码
     */
    public static function previewQrcode($uniacid)
    {
        $app = self::miniProgram($uniacid);
        $commit = static::commit($uniacid, false);
        if ($commit['errcode'] != 0) {
            throw new BadRequestHttpException($commit['errmsg']);
        }
        $res = $app->code->getQrCode('pages/index/index');
        $image = $res->getBody()->getContents();
        $ContentType = $res->getHeader('Content-Type');
        if ($ContentType[0] != "image/jpeg") {
            return false;
        }
        return "data:{$ContentType[0]};base64," . base64_encode($image);
    }

    /**
     * 获取小程序版本信息
     */
    public static function getversioninfo($uniacid)
    {
        $app = self::miniProgram($uniacid);
        $res = $app->code->httpPostJson('wxa/getversioninfo');
        return $res;
    }

    public static function getAuditStatus($uniacid, $auditId)
    {
        $app = self::miniProgram($uniacid);
        return $app->code->getAuditStatus($auditId);
    }

    /**
     * 小程序发布代码
     */
    public static function release($uniacid)
    {
        $app = self::miniProgram($uniacid);
        $res = $app->code->release();
        if ($res['errcode'] == 0) {
            $model = self::getMiniVersion($uniacid);
            $model->state = 9;
            $model->release_time = date("Y-m-d H:i:s", time());
            $model->save();
            return $res;
        }
        return $res;
    }

    /**
     * 绑定体验着
     */
    public static function bindTester($uniacid, $weid = '')
    {
        $app = self::miniProgram($uniacid);
        $res = $app->tester->bind($weid);
        return $res;
    }

    /**
     * 绑定体验着
     */
    public static function unbindTester($uniacid, $weid = '')
    {
        $app = self::miniProgram($uniacid);
        $res = $app->tester->unbind($weid);
        return $res;
    }

    /**
     * 体验者列表
     */
    public static function Testerlist($uniacid)
    {
        $app = self::miniProgram($uniacid);
        $res = $app->tester->list();
        return $res;
    }

    //生产短链接
    static function crateUrlLink($uniacid, $path, $is_expire, $scene, $title, $expire_time = 0)
    {
        $app = self::miniProgram($uniacid);
        $expire_type = 1;
        $expire_time = time() + $expire_time * 60 * 60 * 24;
        if ($is_expire == false) {
            $expire_time = 0;
        }
        $postData = [
            "path" => $path,
            "query" => $scene,
            'is_expire' => $is_expire,
            'expire_type' => $expire_type,
            'expire_interval' => 30, //最长有效期为1年。is_expire 为 true 且 expire_type 为 0 时必填
        ];
        $res = $app->url_link->generate($postData);
        return $res;
    }


    //小程序认证  https://developers.weixin.qq.com/doc/oplatform/openApi/OpenApiDoc/miniprogram-management/weapp-wxverify/secwxaapi_wxaauth.html
    public function secwxaapiWxaauth($uniacid, $customer_type)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        //customer_type企业为1，个体工商户 为12，个人是15，详情参考：
        $url = 'https://api.weixin.qq.com/wxa/sec/wxaauth?access_token=' . $access_token;
        $params = [
            'auth_data' => [
                'customer_type' => $customer_type,
                //'taskid'=>'',//认证任务id，打回重审调用reauth时为必填
                'contact_info' => [
                    'name' => '',//认证联系人姓名
                    'email' => '',//认证联系人邮箱
                    //'mobile'=>''//认证联系人手机号，仅打回重填时可填写
                ],
                'account_name' => '',//小程序账号名称
                'account_name_type' => 1,//小程序账号名称命名类型 1：基于自选词汇命名 2：基于商标命名
                'pay_type' => 1,//支付方式 1：消耗服务商预购包 2：小程序开发者自行支付
                'third_party_phone' => '',//第三方联系电话
                //'service_appid'=>''//选择服务商代缴模式时必填。服务市场appid，该服务市场账号主体必须与服务商账号主体一致

            ]
        ];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success('认证信息已提审');
        } else {
            return $this->failed($res['errmsg']);
        }
    }

    //小程序认证进度查询
    public function secwxaapiQueryauth($uniacid, $taskid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/sec/queryauth?access_token=' . $access_token;
        $params = ['taskid' => $taskid];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

    //小程序认证重新提审
    public function secwxaapiReauth($uniacid, $customer_type, $taskid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/sec/reauth?access_token=' . $access_token;
        $params = [
            'auth_data' => [
                'customer_type' => $customer_type,
                'taskid' => $taskid,//认证任务id，打回重审调用reauth时为必填
                'contact_info' => [
                    'name' => '',//认证联系人姓名
                    'email' => '',//认证联系人邮箱
                    //'mobile'=>''//认证联系人手机号，仅打回重填时可填写
                ],
                'account_name' => '',//小程序账号名称
                'account_name_type' => 1,//小程序账号名称命名类型 1：基于自选词汇命名 2：基于商标命名
                'pay_type' => 2,//支付方式 1：消耗服务商预购包 2：小程序开发者自行支付
                'third_party_phone' => '',//第三方联系电话
                //'service_appid'=>''//选择服务商代缴模式时必填。服务市场appid，该服务市场账号主体必须与服务商账号主体一致

            ]
        ];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success('认证信息已重新提审');
        } else {
            return $this->failed($res['errmsg']);
        }
    }

    //小程序备案
    public function applyIcpFiling($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/apply_icp_filing?access_token=' . $access_token;
        $params = [
            'icp_subject' => [
                'base_info' => [
                    'type' => '',//主体性质，示例值：5
                    'name' => '',//主办单位名称，示例值："张三"
                    'province' => '',//备案省份，使用省份代码，示例值："110000"(参考：获取区域信息接口)
                    'city' => '',//备案城市，使用城市代码，示例值："110100"(参考：获取区域信息接口)
                    'district' => '',//备案县区，使用县区代码，示例值："110105"(参考：获取区域信息接口)
                    'address' => '',//通讯地址，必须属于备案省市区，地址开头的省市区不用填入，例如：通信地址为“北京市朝阳区高碑店路181号1栋12345室”时，只需要填写 "高碑店路181号1栋12345室" 即可
                ],
                'organize_info' => [
                    'certificate_type' => '',//主体证件类型，示例值：2(参考：获取证件类型接口)
                    'certificate_number' => '',//主体证件号码，示例值："110105199001011234"
                    'certificate_address' => '',//主体证件住所，示例值："北京市朝阳区高碑店路181号1栋12345室"
                ],
                'principal_info' => [
                    'name' => '',//负责人姓名，示例值："张三"
                    'mobile' => '',//负责人联系方式，示例值："13012344321"
                    'email' => '',//负责人电子邮件，示例值："zhangsan@zhangsancorp.com"
                    'emergency_contact' => '',//负责人应急联系方式，示例值："17743211234"
                    'certificate_type' => '',//负责人证件类型，示例值：2(参考：获取证件类型接口，此处只能填入单位性质属于个人的证件类型)
                    'certificate_number' => '',//负责人证件号码，示例值："110105199001011234"
                    'certificate_validity_date_start' => '',//负责人证件有效期起始日期，格式为 YYYYmmdd，示例值："20230815"
                    'certificate_validity_date_end' => '',//负责人证件有效期终止日期，格式为 YYYYmmdd，如证件长期有效，请填写 "长期"，示例值："20330815"
                    'certificate_photo_front' => '',//负责人证件正面照片 media_id（身份证为人像面），示例值："4ahCGpd3CYkE6RpkNkUR5czt3LvG8xDnDdKAz6bBKttSfM8p4k5R
                    'certificate_photo_back' => '',//负责人证件背面照片 media_id（身份证为国徽面），示例值："4ahCGpd3CYkE6RpkNkUR5czt3LvG8xDnDdKAz6bBKttSfM8p4k5Rj6823HXugPwQBurgMezyib7"
                ],
                'icp_applets' => [
                    'base_info' => [
                        'service_content_types' => '',//小程序服务内容类型，只能填写二级服务内容类型，最多5个，示例值：[3, 4](参考：获取小程序服务类型接口)
                        'nrlx_details' => [
                            'type' => '',//前置审批类型，示例值：2(参考：获取前置审批项接口)
                        ],
                        'comment' => '',//请具体描述小程序实际经营内容、主要服务内容，该信息为主管部门审核重要依据，备注内容字数限制20-200字，请认真填写。（特殊备注要求请查看注意事项）

                    ],
                ],
                'principal_info' => [
                    'name' => '',//负责人姓名，示例值："张三"
                    'mobile' => '',//负责人联系方式
                    'email' => '',//负责人电子邮件
                    'emergency_contact' => '',//负责人应急联系方式，
                    'certificate_type' => '',//负责人证件类型，示例值：2(参考：获取证件类型接口，此处只能填入单位性质属于个人的证件类型)
                    'certificate_number' => '',//负责人证件号码
                    'certificate_validity_date_start' => '',//负责人证件有效期起始日期，格式为 YYYYmmdd，示例值："20230815"
                    'certificate_validity_date_end' => '',//负责人证件有效期终止日期，格式为 YYYYmmdd，如证件长期有效，请填写 "长期"，示例值："20330815"
                    'certificate_photo_front' => '',//负责人证件正面照片 media_id（身份证为人像面），示例值："4ahCGpd3CYkE6RpkNkUR5czt3LvG8xDnDdKAz6bBKttSfM8p4k5Rj6823HXugPwQBurgM
                    'certificate_photo_back' => '',//负责人证件背面照片 media_id（身份证为国徽面），示例值："4ahCGpd3CYkE6RpkNkUR5czt3LvG8xDnDdKAz6bBKttSfM8p4k5Rj682
                ],
                'icp_materials' => [
                    'commitment_letter' => ''//互联网信息服务承诺书 media_id，最多上传1个
                ],
            ]
        ];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success('备案信息已提交');
        } else {
            return $this->failed($res['errmsg']);
        }
    }

    //获取小程序备案状态及驳回原因
    public function getIcpEntranceInfo($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/get_icp_entrance_info?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

    //获取区域信息
    public function queryIcpDistrictCode($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/query_icp_district_code?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }


//小程序名称检测接口checkNickName
    public static function checkNickName($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_access_token;

        $config=ConfigService::miniConfig($uniacid);
        $url = 'https://api.weixin.qq.com/cgi-bin/wxverify/checkwxverifynickname?access_token=' . $access_token;
        $params = ['nick_name'=>json_decode($config['data'],true)['data']['nick_name']];

        $res = httpRequest($url, $params);
        return $res;
    }

//获取小程序备案前置审批项类型queryIcpNrlxTypes
    public function queryIcpNrlxTypes($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/query_icp_nrlx_types?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//获取小程序备案主体单位性质queryIcpSubjectTypes
    public function queryIcpSubjectTypes($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/query_icp_subject_types?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//获取小程序服务内容类型queryIcpServiceContentTypes
    public function queryIcpServiceContentTypes($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/query_icp_service_content_types?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//获取证件类型queryIcpServiceContentTypes
    public function getIcpServiceContentTypes($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/query_icp_certificate_types?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//上传证件材料接口uploadIcpMedia
    public function uploadIcpMedia($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/upload_icp_media?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//小程序管理员人脸核身接口createIcpVerifyTask
    public function createIcpVerifyTask($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/create_icp_verifytask?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//查询人脸核身任务状态queryIcpVerifyTask
    public function queryIcpVerifyTask($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/icp/query_icp_verifytask?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//代认证及备案小程序接口submitAuthAndIcp
    public function submitAuthAndIcp($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/sec/submit_auth_and_icp?access_token=' . $access_token;
        $params = [
            'auth_data'=>[
                'contact_info'=>[],
                'invoice_info'=>[],//发票信息，如果是服务商代缴模式，不需要填写
                'customer_type'=>1,//认证主体类型：1.企业；12.个体工商户；15.个人
                'pay_type'=>1,//支付方式 1：消耗服务商预购包 2：小程序开发者自行支付
                'account_name'=>'',//小程序账号名称
                'account_name_type'=>1,
                'third_party_phone'=>'',
                'service_appid'=>''//选择服务商代缴模式时必填。服务市场 appid，该服务市场账号主体必须与服务商账号主体一致
            ],
            'icp_subject'=>[
                'base_info'=>[
                    'type'=>5,
                    'name'=>'',//主办单位名称，示例值："张三"
                    'province'=>'',
                    'city'=>'',
                    'district'=>'',
                    'address'=>'',
                ],
                'organize_info'=>[
                    'certificate_type'=>2,
                    'certificate_number'=>'',//主体证件号码
                    'certificate_address'=>'',//主体证件住所
                ],
                'principal_info'=>[
                    'name'=>'',
                    'mobile'=>'',
                    'email'=>'',
                    'emergency_contact'=>'',
                    'certificate_type'=>'',
                    'certificate_number'=>'',
                    'certificate_validity_date_start'=>'',
                    'certificate_validity_date_end'=>'',
                    'certificate_photo_front'=>'',
                    'certificate_photo_back'=>'',
                ],
                'legal_person_info'=>[
                    'name'=>'',
                    'certificate_number'=>'',
                ],
            ],
            'icp_applets'=>[
                'base_info'=>[
                    'service_content_types'=>'',//程序服务内容类型，只能填写二级服务内容类型，最多5个，示例值：[3, 4](参考：获取小程序服务类型接口)
                    'nrlx_details'=>[
                        'type'=>2,//前置审批类型，示例值：2(参考：获取前置审批项接口)
                    ],
                    'comment'=>''//请具体描述小程序实际经营内容、主要服务内容，该信息为主管部门审核重要依据，备注内容字数限制20-200字，请认真填写。（特殊备注要求请查看注意事项）
                ],
            ],
            'icp_materials'=>[],
        ];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }

//查询审核进度接口queryAuthAndIcp
    public function queryAuthAndIcp($uniacid)
    {
        $model = self::getConfig($uniacid, 'mini');
        $access_token = $model->authorizer_refresh_token;
        $url = 'https://api.weixin.qq.com/wxa/sec/query_auth_and_icp?access_token=' . $access_token;
        $params = [];
        $res = httpRequest($url, $params);
        if ($res['errcode'] == 0) {
            return $this->success($res);
        } else {
            return $this->failed($res['errmsg']);
        }
    }
}
