<?php
namespace App\Models;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Message;
use EasyWeChat\Work\GroupRobot\Messages\News;
use EasyWeChat\Work\GroupRobot\Messages\Text;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use EasyWeChat\Work\Application;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class WeCom extends BaseModel{
    //应用密钥FPrBZZ84PVXGnDcBFxY2qKYG7694w6MBOd1WayWwmuk
    //通讯录密钥IQyeazV0HTF_P1SppfG2TIUYVmfOmOYi6bAhRX0rtUs
    //客户密钥jEglS46YT4ekKNGs1KQwWW8qzlBIL3pcgZDCVwQAhGM
    //客户token    X5NMrrs80bW
    //客户EncodingAESKey   AIlYcdJdQ3jEWSnQws6Jp0C4HPbavlpnwYHW41iRgd7
    //企业IDwwe716029235bc6130
    //获取菜单
    //应用ID1000005
    public static function getConfig(){
        $config = [
            'corp_id' => 'wwe716029235bc6130',
            'agent_id' => 1000005, // 如果有 agend_id 则填写
            'secret'   => 'FPrBZZ84PVXGnDcBFxY2qKYG7694w6MBOd1WayWwmuk',
            'token' => 'xyA4HGFPxfYgtwjNEMN7fHpdAK',
            'aes_key' => 'ImU9l3tb55jJMvJD1edRB3gmNle7idSSwpfoHq2TB3d',
        ];
        return $config;
    }
    public static function telConfig(){
        $config = [
            'corp_id' => 'wwe716029235bc6130',
            'secret'   => 'IQyeazV0HTF_P1SppfG2TIUYVmfOmOYi6bAhRX0rtUs',
        ];
        return $config;
    }

    public static function customerConfig(){
        $config = [
            'corp_id' => 'wwe716029235bc6130',
            'token' => 'X5NMrrs80bW',
            'aes_key' => 'AIlYcdJdQ3jEWSnQws6Jp0C4HPbavlpnwYHW41iRgd7',
            'secret'   => 'jEglS46YT4ekKNGs1KQwWW8qzlBIL3pcgZDCVwQAhGM',
        ];
        return $config;
    }
    public static function workProgram($applicationType=1){
        switch ($applicationType){
            case 1;
                $config=self::getConfig();
            break;
            case 2;
                $config=self::telConfig();
                break;
            case 3;
                $config=self::customerConfig();
            break;
        }
        $app = Factory::work($config);
        return $app;
    }
    //修改用户备注
    //获取配置了客户联系功能的成员列表
    public static function getFollowUserList(){
        $app=self::workProgram(3);
        $response = $app->httpGet('cgi-bin/externalcontact/get_follow_user_list');
        dd($response);
    }
    //发送新客户欢迎语
    public static function sendWelcomeMsg($welcome_code){
        $app=self::workProgram(3);
        $param=[
            'welcome_code'=>$welcome_code,
            'text'=>[
                'content'=>'速信v2连锁|o2o年终特惠活动来了'.PHP_EOL.'每天10名先到先得联系客服有特殊优惠'
            ],
            'attachments'=>[
                array(
                'msgtype'=>'link',
                'link'=>array(
                    'title'=>'速信v2连锁|o2o',
                    'picurl'=>'https://cdn.w7.cc/images/2022/07/27/LV5So1OUm78nJTjlH4J3wXEUCxEP0zEPkZLF4KyJ.jpg',
                    'desc'=>'年终特惠送两年续费',
                    'url'=>'https://www.b-ke.cn'
                       )
                ),
//                array(
//                    'msgtype'=>'miniprogram',
//                    'miniprogram'=>array(
//                        'title'=>'速信v2连锁外卖点餐系统',
//                        'pic_media_id'=>'BPJbydxOEptLAsvL-xacwx2eH2XI4I3tYIKQrTKuaFS6KZZL1_7pESjbPQP82S8N',
//                        'appid'=>'wx1f58754e6d35e3c7',
//                        'page'=>'yb_wm/index/goods'
//                    )
//                ),
            ],
        ];
        $response = $app->httpPostJson('cgi-bin/externalcontact/send_welcome_msg',$param);
        file_put_contents('event.log', json_encode($response).PHP_EOL, FILE_APPEND);
        return $response;

    }
    //获取登录人信息
    public static function getuserinfo($code){
        $app=self::workProgram();
        $response = $app->httpGet('cgi-bin/auth/getuserinfo',['code'=>$code]);
        dd($response);
    }
    //获取客户详情
    public static function getexternalcontact(){
        $app=self::workProgram();
        $response = $app->httpPostJson('cgi-bin/externalcontact/get',['external_userid'=>'viva']);
        dd($response);
    }


     //获取客户列表
    public static function groupchatList(){
        $app=self::workProgram(3);
        $response = $app->httpPostJson('cgi-bin/externalcontact/groupchat/list',['limit'=>1000]);
        return $response;
    }
    //获取客户群详情
    public static function groupchat($chat_id){
        $app=self::workProgram(3);
        $response = $app->httpPostJson('cgi-bin/externalcontact/groupchat/get',['chat_id'=>$chat_id]);
        return $response;
    }
    //手机号获取userid
    public static function getuserid(){
        $app=self::workProgram();
        $response = $app->httpPostJson('cgi-bin/user/getuserid',['mobile'=>17607186026]);
        dd($response);
    }
    //获取成员ID列表
    public static function getUserList(){
        $app=self::workProgram(2);
        $response = $app->httpPostJson('cgi-bin/user/list_id',['cursor'=>'','limit'=>10000]);
        dd($response);
    }

    //创建部门
    public static function createDepartment(){
        $app=self::workProgram(2);
        $response = $app->httpPostJson('cgi-bin/department/create',['name'=>'大雕','name_en'=>'dadiao','parentid'=>1]);
        dd($response);
    }
    //获取部门列表
    public static function getDepartment($id=''){
        $app=self::workProgram();
        $data=$app->department->list();
        if($id){
            $data=$app->department->list($id);
        }
        return $data;
    }
    //获取部门下成员
    public static function getDepartmentUsers($departmentId){
        $app=self::workProgram();
        $data=$app->user->getDepartmentUsers($departmentId);
        // 递归获取子部门下面的成员
        //$data=$app->user->getDepartmentUsers($departmentId, true);
        return $data;
    }
    //获取部门成员详情
    public static function getDetailedDepartmentUsers($departmentId){
        $app=self::workProgram();
        //$data=$app->user->getDetailedDepartmentUsers($departmentId);
        // 递归获取子部门下面的成员
        $data=$app->user->getDetailedDepartmentUsers($departmentId, true);
        return $data;
    }
    //用户 ID 转为 openid
    public static function userIdToOpenid($userId){
        $app=self::workProgram();
        $data=$app->user->userIdToOpenid($userId);
        return $data;
    }
    //Apit调用
    public static function sendRequest(){
        $app=self::workProgram();
        $param='{
            "touser" : "viva",
           "toparty": "",
           "totag": "",
           "msgtype" : "textcard",
           "agentid": 1000005,
           "textcard":{"title" : "领奖通知","description" : "\n<div class=\"gray\">恭喜您中奖Apple手机一台，请一分钟内速来前台领奖，过期作废！！！</div>","url" : "www.baidu.com","btntxt":"更多"}
        }';
//        $param='{
//           "touser" : "viva",
//           "toparty": 1,
//           "totag": "",
//           "msgtype" : "miniprogram_notice",
//           "miniprogram_notice" : {
//                "appid": "wx1f58754e6d35e3c7",
//                "page": "yb_wm/my/coupon/center",
//                "title": "福利送券了",
//                "description": "1月11日 16:16",
//                "emphasis_first_item": true,
//                "content_item": [
//                    {
//                        "key": "年终活动",
//                        "value": "十周年庆年终活动开始了!"
//                    },
//                    {
//                        "key": "活动时间",
//                        "value": "十周年庆祝"
//                    },
//                    {
//                        "key": "会议时间",
//                        "value": "2018年8月1日 09:00-09:30"
//                    },
//                    {
//                        "key": "参与人员",
//                        "value": "@所有人"
//                    }
//                ]
//            },
//           "enable_id_trans": 0,
//           "enable_duplicate_check": 0,
//           "duplicate_check_interval": 1800
//        }';
        $response = $app->httpPostJson('cgi-bin/message/send',json_decode($param,true));
        var_dump($response);
    }

    //修改企业微信菜单
    public static function createMenus($menus){
        $app=self::workProgram(2);
//        $menus = [
//            'button' => [
//                [
//                    'name' => '首页',
//                    'type' => 'view',
//                    'url' => 'https://easywechat.com'
//                ],
//                [
//                    'name' => '关于我们',
//                    'type' => 'view',
//                    'url' => 'https://easywechat.com/about'
//                ],
//                //...
//            ],
//        ];
        $data=$app->menu->create($menus);
        return $data;
    }

    //机器人发送文本消息
    public static function sendText(){
        $app=WeCom::workProgram();
        $text = new Text('hello');
        $messenger = $app->group_robot_messenger;
        $groupKey='fe2bc246-b8d9-4913-ae27-f5721e391e98';
        $messenger->message($text)->toGroup($groupKey)->send();
    }
    //机器人发送图文消息
    public static function sendNews(){
        $app=WeCom::workProgram();
        $groupKey='fe2bc246-b8d9-4913-ae27-f5721e391e98';
        $messenger = $app->group_robot_messenger;
        $items = [
            new NewsItem([
                'title' => '中秋节礼品领取',
                'description' => '今年中秋节公司有豪礼相送',
                'url' => 'https://www.easywechat.com',
                'image' => 'http://res.mail.qq.com/node/ww/wwopenmng/images/independent/doc/test_pic_msg1.png',
            ]),
        ];
        $news = new News($items);
        $messenger->message($news)->toGroup($groupKey)->send();
    }

    //获取邀请二维码
    public static function getInvitationImage(){
        $app=WeCom::workProgram(2);
        $sizeType = 1;  // qrcode尺寸类型，1: 171 x 171; 2: 399 x 399; 3: 741 x 741; 4: 2052 x 2052
        $data=$app->user->getInvitationQrCode($sizeType);
        return $data;
    }


}
