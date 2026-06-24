<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Admin
 *
 * @property int $id
 * @property string $username 用户名
 * @property string $mobile 手机号
 * @property string $password 密码
 * @property string $nickname 昵称
 * @property string $avatar 头像
 * @property string $ip 登陆IP
 * @property int $role_id 权限等级
 * @property int $type 用户类型0总管理员1平台管理员2管理员3区域代理4区域管理员
 * @property int $uniacid 平台id:0总后台管理员
 * @property string|null $login_time 登陆时间
 * @property string|null $last_login_time 最后一次登陆
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $createStoreNum 可创建门店数量
 * @property int $status 状态1启用2禁用
 * @property array|null $data 存储的套餐数据集合
 * @property int|null $isAdmin 是否是业务主管理员
 * @property int|null $group_id 用户分组id
 * @property int $channel 来源1:后台添加,2:注册
 * @property string|null $superPassword
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Admin\Apply[] $adminApply
 * @property-read int|null $admin_apply_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Admin\Apply[] $apply
 * @property-read int|null $apply_count
 * @property-read mixed $status_format
 * @property-read \App\Models\AdminGroup|null $group
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Role|null $role
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newQuery()
 * @method static \Illuminate\Database\Query\Builder|Admin onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereCreateStoreNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereLastLoginTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereLoginTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereSuperPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|Admin withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Admin withoutTrashed()
 */
	class Admin extends \Eloquent implements \Tymon\JWTAuth\Contracts\JWTSubject {}
}

namespace App\Models{
/**
 * App\Models\AdminGroup
 *
 * @property int $id
 * @property int $sort 用户Id
 * @property string $title 类型:lite/wechat/ali/toutiao
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $service
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Admin[] $admins
 * @property-read int|null $admins_count
 * @property-read \App\Models\Customer|null $customer
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminGroup whereUpdatedAt($value)
 */
	class AdminGroup extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\AdminOrder
 *
 * @property int $id
 * @property string $outTradeNo
 * @property string $money
 * @property int $goodsId
 * @property int $type
 * @property int $userId
 * @property int $applyId
 * @property int $state
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $payType
 * @property string|null $transaction_id
 * @property int $day
 * @property array $attach
 * @property-read \App\Models\Admin\Apply|null $apply
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereApplyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereAttach($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereGoodsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereOutTradeNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder wherePayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminOrder whereUserId($value)
 */
	class AdminOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\AdminRole
 *
 * @property int $id
 * @property int $admin_id
 * @property int $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole query()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole whereUpdatedAt($value)
 */
	class AdminRole extends \Eloquent {}
}

namespace App\Models\Admin{
/**
 * App\Models\Admin\AdminBind
 *
 * @property int $id
 * @property int $userId 用户Id
 * @property string $type 类型:lite/wechat/ali/toutiao
 * @property string|null $unionid unionid
 * @property string $openid openid
 * @property string $channel 频道
 * @property array $data 数据
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Admin|null $Admin
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind query()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereOpenid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereUnionid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBind whereUserId($value)
 */
	class AdminBind extends \Eloquent {}
}

namespace App\Models\Admin{
/**
 * App\Models\Admin\Apply
 *
 * @property int $id
 * @property string $applyName 平台名称
 * @property string $applyImage 平台图片
 * @property string $musterId 平台套餐ID
 * @property int|null $applyType 平台类型
 * @property int|null $createCount 可创建门店数量
 * @property string|null $startTime 有效时间开始时间
 * @property string|null $endTime 有效时间结束时间
 * @property int $status 状态6待审核1正常2拉黑3已过期4删除5审核不通过
 * @property array $plugStr 插件ID组合
 * @property int $timeType 有效时间类型1无限2自定义时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $createUserId 创建人
 * @property int|null $attachmentType 附件存储类型0:平台,1本地,2阿里云oss,3七牛云，4腾讯云
 * @property string|null $notes 备注
 * @property int $plugType 插件类型
 * @property int $sort 排序
 * @property int $day 有效天数
 * @property int $adminId
 * @property array|null $address
 * @property array $attachmentData 存储设置内容
 * @property int $copyrightSwitch
 * @property array|null $copyright
 * @property int $type
 * @property array|null $smsSign 短信签名配置
 * @property int $storeNum
 * @property int $storeNumInfinite
 * @property int $payChange
 * @property-read \App\Models\Admin|null $admin
 * @property-read mixed $type_format
 * @property-read \App\Models\Setmeal|null $muster
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Admin[] $operator
 * @property-read int|null $operator_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ApplyPlugs[] $plugs
 * @property-read int|null $plugs_count
 * @property-read \App\Models\SmsAccount|null $smsAccount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store[] $store
 * @property-read int|null $store_count
 * @method static \Illuminate\Database\Eloquent\Builder|Apply audit()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply black()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply normal()
 * @method static \Illuminate\Database\Query\Builder|Apply onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply overdue()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply pass()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply query()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply rejected()
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereApplyImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereApplyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereApplyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereAttachmentData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereAttachmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereCopyright($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereCopyrightSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereCreateCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereCreateUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereMusterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply wherePayChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply wherePlugStr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply wherePlugType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereSmsSign($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereStoreNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereStoreNumInfinite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereTimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Apply whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Apply withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Apply withoutTrashed()
 */
	class Apply extends \Eloquent {}
}

namespace App\Models\Admin{
/**
 * App\Models\Admin\HandleLog
 *
 * @property int $id
 * @property int|null $userId 操作用户ID
 * @property string|null $username 操作用户名
 * @property string $route 请求的路由地址
 * @property string $input 提交的请求参数
 * @property string $method 请求方式
 * @property string $ip 请求的IP地址
 * @property int $type 1登录2操作
 * @property int $uniacid 平台ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $channel
 * @property-read \App\Models\Admin|null $admin
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUsername($value)
 */
	class HandleLog extends \Eloquent {}
}

namespace App\Models\Admin{
/**
 * App\Models\Admin\Index
 *
 * @property-read mixed $apply_count
 * @property-read mixed $apply_recycle
 * @property-read mixed $apply_top
 * @property-read mixed $apply_try_out
 * @property-read mixed $apply_try_out_exceed
 * @property-read mixed $authorize_data
 * @property-read mixed $mini_update
 * @property-read mixed $new_apply
 * @property-read mixed $order_money
 * @property-read mixed $payment_overview
 * @property-read mixed $store_user_profile
 * @property-read mixed $system_update
 * @property-read mixed $today_apply_count
 * @property-read mixed $today_order_count
 * @property-read mixed $today_order_money
 * @property-read mixed $today_user_count
 * @property-read mixed $user_count
 * @property-read mixed $view_data
 * @property-read mixed $yesterday_apply_count
 * @property-read mixed $yesterday_order_count
 * @property-read mixed $yesterday_order_money
 * @property-read mixed $yesterday_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|Index newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Index newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Index query()
 */
	class Index extends \Eloquent {}
}

namespace App\Models\Admin{
/**
 * App\Models\Admin\Muster
 *
 * @property int $id
 * @property string $musterName 平台套餐名称
 * @property int $musterType 到期时间1无限时间2自定义时间
 * @property string|null $musterDay 有效期/天
 * @property array|null $plugStr 插件权限
 * @property string $status 是否开启1开启2关闭
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Muster newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Muster newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Muster query()
 * @method static \Illuminate\Database\Eloquent\Builder|Muster whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Muster whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Muster whereMusterDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Muster whereMusterName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Muster whereMusterType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Muster wherePlugStr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Muster whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Muster whereUpdatedAt($value)
 */
	class Muster extends \Eloquent {}
}

namespace App\Models\Admin{
/**
 * App\Models\Admin\Order
 *
 * @property int $id
 * @property string $outTradeNo
 * @property string $money
 * @property int $goodsId
 * @property int $type
 * @property int $userId
 * @property int $applyId
 * @property int $state
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $payType
 * @property string|null $transaction_id
 * @property int $day
 * @property string $attach
 * @property-read \App\Models\Admin\Apply|null $apply
 * @property-read mixed $pay_type_format
 * @property-read mixed $type_format
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereApplyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAttach($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereGoodsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOutTradeNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserId($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ApplyPlugs
 *
 * @property int $id
 * @property int $plugId
 * @property int $source
 * @property int $state 状态0未授权1已授权
 * @property int $display 1显示2隐藏
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $startTime
 * @property int $uniacid
 * @property string|null $endTime
 * @property-read \App\Models\Plug|null $plug
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs query()
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs wherePlugId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApplyPlugs whereUpdatedAt($value)
 */
	class ApplyPlugs extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Attr
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property string $name 标签名称
 * @property array $value 属性值
 * @property int $multipleSwitch 是否多选
 * @property int $mustSwitch 是否必选
 * @property string|null $desc 描述
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $notes
 * @method static \Illuminate\Database\Eloquent\Builder|Attr newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Attr newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Attr query()
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereMultipleSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereMustSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attr whereValue($value)
 */
	class Attr extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\BaseModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel query()
 */
	class BaseModel extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\BrowseCircle
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BrowseCircle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BrowseCircle newQuery()
 * @method static \Illuminate\Database\Query\Builder|BrowseCircle onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|BrowseCircle query()
 * @method static \Illuminate\Database\Query\Builder|BrowseCircle withTrashed()
 * @method static \Illuminate\Database\Query\Builder|BrowseCircle withoutTrashed()
 */
	class BrowseCircle extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Category
 *
 * @property int $id
 * @property string $sort 排序
 * @property int $item 1图片分类
 * @property string $name 分类名称
 * @property string $icon 分类图片
 * @property int $uniacid 平台ID
 * @property int $shopId 商户ID
 * @property string|null $deleteAt 删除时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereDeleteAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereUpdatedAt($value)
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Ceshi
 *
 * @property string $a
 * @property string $b
 * @property string $c
 * @property string $d
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property int $id
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi query()
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi whereA($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi whereB($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi whereC($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi whereD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ceshi whereUpdatedAt($value)
 */
	class Ceshi extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ChannelConfig
 *
 * @property int $id
 * @property string $data
 * @property int $uniacid 平台
 * @property string $ident 标识
 * @property string $name 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereIdent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelConfig whereUpdatedAt($value)
 */
	class ChannelConfig extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ChannelNotice
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelNotice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelNotice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChannelNotice query()
 */
	class ChannelNotice extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Config
 *
 * @property int $id
 * @property string $data
 * @property int $uniacid 平台
 * @property string $ident 标识
 * @property string $identName 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|Config newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Config newQuery()
 * @method static \Illuminate\Database\Query\Builder|Config onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Config query()
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereIdent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereIdentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Config withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Config withoutTrashed()
 */
	class Config extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Customer
 *
 * @property int $id
 * @property int $sort 排序
 * @property string $userName 客服姓名
 * @property string $contact_information 客服联系方式
 * @property string $qq 客服qq
 * @property string $wechat_qrcode 客服微信二维码
 * @property int $state 1正常2禁用
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereContactInformation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereQq($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereUserName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereWechatQrcode($value)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models\Delivery{
/**
 * App\Models\Delivery\Channel
 *
 * @property int $id
 * @property int $uniacid
 * @property array|null $config
 * @property int $storeId
 * @property int $type 1 麦芽田2马科
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $channelId
 * @method static \Illuminate\Database\Eloquent\Builder|Channel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel query()
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Channel whereUpdatedAt($value)
 */
	class Channel extends \Eloquent {}
}

namespace App\Models\Delivery{
/**
 * App\Models\Delivery\Rule
 *
 * @property int $id
 * @property int $uniacid
 * @property string $name 名称
 * @property string|null $desc 简介
 * @property array|null $channel 配送渠道 1麦芽田2马科
 * @property int $deliveryType 配送方 1平台配送 2商家自配送
 * @property array $deliveryData 自定义配送数据
 * @property int $receivingMinutes 订单接单后分钟开始呼叫
 * @property int $advanceOrderMinutes 预订单订单接单后分钟开始呼叫
 * @property int $advanceOrderType 预订单类型 1接单后 2 预约时间前
 * @property int $loseType 呼叫失败后处理 1手动呼叫2重试 3退款
 * @property int $loseNum 重试次数
 * @property int $kmMinutes 3千米内配送时间
 * @property int $kmPushMinutes 3千米外每千米配送分钟
 * @property int $km 配送半径Km
 * @property int $priceType 1固定2距离3区域
 * @property array|null $priceFixData 固定价格参数
 * @property array|null $priceDistanceData 按距离配送参数
 * @property array|null $priceAreaData 按区域配送参数
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property array|null $startRule 起送条件
 * @property int $estimate 预计送达
 * @property int $callType 0自定义,1最快 2最省
 * @property int $makeMinutes 制作时长
 * @method static \Illuminate\Database\Eloquent\Builder|Rule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereAdvanceOrderMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereAdvanceOrderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereCallType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereDeliveryData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereDeliveryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereEstimate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereKmMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereKmPushMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereLoseNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereLoseType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereMakeMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule wherePriceAreaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule wherePriceDistanceData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule wherePriceFixData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule wherePriceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereReceivingMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereStartRule($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rule whereUpdatedAt($value)
 */
	class Rule extends \Eloquent {}
}

namespace App\Models\Delivery{
/**
 * App\Models\Delivery\Store
 *
 * @property int $id
 * @property int $uniacid
 * @property string $name 名称
 * @property string|null $desc 简介
 * @property array|null $channel 配送渠道 1麦芽田2马科
 * @property int $deliveryType 配送方1商家 2门店自定义
 * @property array $deliveryData 自定义配送数据
 * @property int $receivingMinutes 订单接单后分钟开始呼叫
 * @property int $advanceOrderMinutes 预订单订单接单后分钟开始呼叫
 * @property int $advanceOrderType 预订单类型 1接单后 2 预约时间前
 * @property int $loseType 呼叫失败后处理 1手动呼叫2重试 3退款
 * @property int $loseNum 重试次数
 * @property int $kmMinutes 3千米内配送时间
 * @property int $kmPushMinutes 3千米外每千米配送分钟
 * @property int $km 配送半径Km
 * @property int $priceType 1固定2距离3区域
 * @property array|null $priceFixData 固定价格参数
 * @property array|null $priceDistanceData 按距离配送参数
 * @property array|null $priceAreaData 按区域配送参数
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property array|null $startRule 起送条件
 * @property int $estimate 预计送达
 * @property int $storeId
 * @property int $callType 0自定义1最快2最省
 * @property int $ruleId
 * @property int $makeMinutes
 * @property-read \App\Models\Delivery\Rule|null $rule
 * @property-read \App\Models\Store|null $store
 * @method static \Illuminate\Database\Eloquent\Builder|Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Store newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Store query()
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereAdvanceOrderMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereAdvanceOrderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereCallType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereDeliveryData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereDeliveryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereEstimate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereKmMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereKmPushMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereLoseNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereLoseType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereMakeMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store wherePriceAreaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store wherePriceDistanceData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store wherePriceFixData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store wherePriceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereReceivingMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereStartRule($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUpdatedAt($value)
 */
	class Store extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Douyin
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Douyin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Douyin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Douyin query()
 */
	class Douyin extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Drag
 *
 * @property int $id
 * @property string|null $title
 * @property int $uniacid
 * @property string $type
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $appType 装修渠道
 * @property int $channel 1小程序2公众号
 * @property int $state
 * @method static \Illuminate\Database\Eloquent\Builder|Drag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Drag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Drag query()
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereAppType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Drag whereUpdatedAt($value)
 */
	class Drag extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\File
 *
 * @property int $id
 * @property int $shopId 门店Id
 * @property string $categoryId 图片分类ID
 * @property string $url 图片地址
 * @property string $uniacid 平台ID
 * @property string|null $deleteAt 删除时间
 * @property string $name 图片名称
 * @property string $fileType 图片类型
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $fileSize
 * @property int $channel 上传渠道0本地1七牛2阿里3腾讯4ftp
 * @property string|null $path
 * @property string|null $domain
 * @property int $width
 * @property int $height
 * @property int|null $material_type 素材类型1图片2音频3视频4回收站
 * @method static \Illuminate\Database\Eloquent\Builder|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|File query()
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereDeleteAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereMaterialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereWidth($value)
 */
	class File extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsCat
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property int $labelId 分类标签Id
 * @property string $name 标签名称
 * @property string|null $logo logo
 * @property int $isMust 是否必须
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $notes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $goods
 * @property-read int|null $goods_count
 * @property-read \App\Models\GoodsCatLabel|null $label
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereIsMust($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCat whereUpdatedAt($value)
 */
	class GoodsCat extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsCatLabel
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property string $name 标签名称
 * @property string|null $bgColor 背景颜色
 * @property string|null $textColor 背景颜色
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereBgColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereTextColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsCatLabel whereUpdatedAt($value)
 */
	class GoodsCatLabel extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsContent
 *
 * @property int $id
 * @property int $uniacid 平台Id
 * @property int $spuId 商品id
 * @property string $content 商品详情
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsContent whereUpdatedAt($value)
 */
	class GoodsContent extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsLabel
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property string $name 标签名称
 * @property string|null $bgColor 背景颜色
 * @property string|null $textColor 背景颜色
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $notes
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereBgColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereTextColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsLabel whereUpdatedAt($value)
 */
	class GoodsLabel extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsMark
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property string $name 标签名称
 * @property string|null $bgColor 背景颜色
 * @property string|null $startTime 开始时间
 * @property string|null $endTime 结束时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereBgColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsMark whereUpdatedAt($value)
 */
	class GoodsMark extends \Eloquent {}
}

namespace App\Models\GoodsRecommend{
/**
 * App\Models\GoodsRecommend\Goods
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $recommendId 模板id
 * @property int $spuId 商品ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $state
 * @property string|null $deleted_at
 * @property int $type
 * @property-read \App\Models\GoodsSpu|null $spu
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods query()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereRecommendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Goods whereUpdatedAt($value)
 */
	class Goods extends \Eloquent {}
}

namespace App\Models\GoodsRecommend{
/**
 * App\Models\GoodsRecommend\Recommend
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property string $name 名称
 * @property string $desc 描述
 * @property int $type 类型:1外卖2堂食
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $sort
 * @property int $state
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsRecommend\Goods[] $goods
 * @property-read int|null $goods_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsRecommend\Store[] $store
 * @property-read int|null $store_count
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend query()
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recommend whereUpdatedAt($value)
 */
	class Recommend extends \Eloquent {}
}

namespace App\Models\GoodsRecommend{
/**
 * App\Models\GoodsRecommend\Store
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $recommendId 模板id
 * @property int $storeId 门店id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Store|null $store
 * @method static \Illuminate\Database\Eloquent\Builder|Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Store newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Store query()
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereRecommendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUpdatedAt($value)
 */
	class Store extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsSku
 *
 * @property int $id
 * @property int $sort 排序
 * @property array|null $specName 编码
 * @property string $specMd5 编码
 * @property int $type 商品类型:1单规格,2多规格
 * @property int $uniacid 平台Id
 * @property int $spuId spuId
 * @property string $price 销售价
 * @property string $linePrice 划线价
 * @property string $costPrice 成本价
 * @property int $inventory 库存
 * @property int $component 分量
 * @property int $dayFilling 次日补齐
 * @property string|null $barcode 条码
 * @property string|null $sn 编码
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $boxMoney
 * @property int $state
 * @property int $sales
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku newQuery()
 * @method static \Illuminate\Database\Query\Builder|GoodsSku onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereBoxMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereComponent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereCostPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereDayFilling($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereLinePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereSpecMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereSpecName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSku whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|GoodsSku withTrashed()
 * @method static \Illuminate\Database\Query\Builder|GoodsSku withoutTrashed()
 */
	class GoodsSku extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsSpu
 *
 * @property int $id
 * @property int $sort 排序
 * @property string $name 商品名称
 * @property int $type 商品类型:1实物商品,2加料商品
 * @property int $uniacid 平台Id
 * @property array|null $catId 商品分类
 * @property array|null $labelId 商品标签id
 * @property int $markId 商品角标id
 * @property string|null $pinYin 拼音助记码
 * @property int $initialSales 初始销量
 * @property int $sales 实际销量
 * @property int $isExhibition 是否为展示商品
 * @property int $unitId 单位
 * @property string|null $logo 拼音助记码
 * @property string|null $cover 封面
 * @property array $images 详情图
 * @property string|null $video 视频
 * @property int $isShow 是否为展示商品
 * @property int $specSwitch 规格开关
 * @property array $specData 规格数据
 * @property int $attrSwitch 属性开关
 * @property array $attrData 属性数据
 * @property int $materialSwitch 加料开关
 * @property array $materialData 加料数据
 * @property int $salesTimeSwitch 时段销售开关
 * @property array $salesTimeData 时段销售数据
 * @property int $salesType 售卖方式 1:可单独购买份数0:赠品或套餐商品
 * @property int $orderlimitSwitch 每单限购开关
 * @property int $orderlimit 每单限购数量
 * @property int $userlimitSwitch 每人限购开关
 * @property int $userlimit 每人限购数量
 * @property int $daylimitSwitch 每天限购开关
 * @property int $daylimit 每天限购数量
 * @property int $oneDeliverySwitch 单点不送
 * @property string|null $shareTitle 分享标题
 * @property string|null $shareImage 分享图片
 * @property string|null $shareNotes 分享备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $min 最小购买份数
 * @property string|null $desc 简介
 * @property int $vipPriceSwitch
 * @property int $state
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsCat[] $category
 * @property-read int|null $category_count
 * @property-read \App\Models\GoodsContent|null $content
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsLabel[] $label
 * @property-read int|null $label_count
 * @property-read \App\Models\GoodsMark|null $mark
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Recipe\RecipeGoods[] $recipeGoods
 * @property-read int|null $recipe_goods_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsRecommend\Goods[] $recommendGoods
 * @property-read int|null $recommend_goods_count
 * @property-read \App\Models\GoodsSku|null $singleSpec
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsSku[] $skus
 * @property-read int|null $skus_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store\StoreGoods[] $storeGoods
 * @property-read int|null $store_goods_count
 * @property-read \App\Models\GoodsUnit|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu offShelf()
 * @method static \Illuminate\Database\Query\Builder|GoodsSpu onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu shelf()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu stateCount()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereAttrData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereAttrSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereCover($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereDaylimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereDaylimitSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereInitialSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereIsExhibition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereIsShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereMarkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereMaterialData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereMaterialSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereOneDeliverySwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereOrderlimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereOrderlimitSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu wherePinYin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereSalesTimeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereSalesTimeSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereSalesType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereShareImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereShareNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereShareTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereSpecData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereSpecSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereUserlimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereUserlimitSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereVideo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu whereVipPriceSwitch($value)
 * @method static \Illuminate\Database\Query\Builder|GoodsSpu withTrashed()
 * @method static \Illuminate\Database\Query\Builder|GoodsSpu withoutTrashed()
 */
	class GoodsSpu extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\GoodsUnit
 *
 * @property int $id
 * @property string $name 单位名称
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $uniacid
 * @property int $sort
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsUnit whereUpdatedAt($value)
 */
	class GoodsUnit extends \Eloquent {}
}

namespace App\Models\Goods{
/**
 * App\Models\Goods\SpuList
 *
 * @property int $id
 * @property int $sort 排序
 * @property string $name 商品名称
 * @property int $type 商品类型:1实物商品,2加料商品
 * @property int $uniacid 平台Id
 * @property array|null $catId 商品分类
 * @property array|null $labelId 商品标签id
 * @property int $markId 商品角标id
 * @property string|null $pinYin 拼音助记码
 * @property int $initialSales 初始销量
 * @property int $sales 实际销量
 * @property int $isExhibition 是否为展示商品
 * @property int $unitId 单位
 * @property string|null $logo 拼音助记码
 * @property string|null $cover 封面
 * @property array $images 详情图
 * @property string|null $video 视频
 * @property int $isShow 是否为展示商品
 * @property int $specSwitch 规格开关
 * @property array $specData 规格数据
 * @property int $attrSwitch 属性开关
 * @property array $attrData 属性数据
 * @property int $materialSwitch 加料开关
 * @property array $materialData 加料数据
 * @property int $salesTimeSwitch 时段销售开关
 * @property array $salesTimeData 时段销售数据
 * @property int $salesType 售卖方式 1:可单独购买份数0:赠品或套餐商品
 * @property int $orderlimitSwitch 每单限购开关
 * @property int $orderlimit 每单限购数量
 * @property int $userlimitSwitch 每人限购开关
 * @property int $userlimit 每人限购数量
 * @property int $daylimitSwitch 每天限购开关
 * @property int $daylimit 每天限购数量
 * @property int $oneDeliverySwitch 单点不送
 * @property string|null $shareTitle 分享标题
 * @property string|null $shareImage 分享图片
 * @property string|null $shareNotes 分享备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $min 最小购买份数
 * @property string|null $desc 简介
 * @property int $vipPriceSwitch
 * @property int $state
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsCat[] $category
 * @property-read int|null $category_count
 * @property-read \App\Models\GoodsContent|null $content
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsLabel[] $label
 * @property-read int|null $label_count
 * @property-read \App\Models\GoodsMark|null $mark
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Recipe\RecipeGoods[] $recipeGoods
 * @property-read int|null $recipe_goods_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsRecommend\Goods[] $recommendGoods
 * @property-read int|null $recommend_goods_count
 * @property-read \App\Models\GoodsSku|null $singleSpec
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsSku[] $skus
 * @property-read int|null $skus_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store\StoreGoods[] $storeGoods
 * @property-read int|null $store_goods_count
 * @property-read \App\Models\GoodsUnit|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu offShelf()
 * @method static \Illuminate\Database\Query\Builder|SpuList onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu shelf()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsSpu stateCount()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereAttrData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereAttrSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereCover($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereDaylimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereDaylimitSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereInitialSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereIsExhibition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereIsShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereMarkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereMaterialData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereMaterialSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereOneDeliverySwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereOrderlimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereOrderlimitSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList wherePinYin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereSalesTimeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereSalesTimeSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereSalesType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereShareImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereShareNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereShareTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereSpecData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereSpecSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereUserlimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereUserlimitSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereVideo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuList whereVipPriceSwitch($value)
 * @method static \Illuminate\Database\Query\Builder|SpuList withTrashed()
 * @method static \Illuminate\Database\Query\Builder|SpuList withoutTrashed()
 */
	class SpuList extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\HandleLog
 *
 * @property int $id
 * @property int|null $userId 操作用户ID
 * @property string|null $username 操作用户名
 * @property string $route 请求的路由地址
 * @property string $input 提交的请求参数
 * @property string $method 请求方式
 * @property string $ip 请求的IP地址
 * @property int $type 1登录2操作
 * @property int $uniacid 平台ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $channel
 * @property-read \App\Models\Admin|null $admin
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HandleLog whereUsername($value)
 */
	class HandleLog extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Hardware
 *
 * @property int $id id
 * @property int $uniacid 店铺id
 * @property int $storeId 门店id
 * @property int $type 设备类型 1小票机2标签机3云音响
 * @property string|null $vendor esLink 易联云 feie 飞蛾
 * @property array|null $config 配置
 * @property int $ruleId 打印规则
 * @property int $sort
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\PrintRule|null $rule
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware query()
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hardware whereVendor($value)
 */
	class Hardware extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Install
 *
 * @property int $id
 * @property string|null $type
 * @property string|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Install newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Install newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Install query()
 * @method static \Illuminate\Database\Eloquent\Builder|Install whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Install whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Install whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Install whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Install whereUpdatedAt($value)
 */
	class Install extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Material
 *
 * @property int $id
 * @property int $sort 排序
 * @property int $uniacid 平台Id
 * @property string $name 加料商品名称
 * @property string $sn 加料商品编码
 * @property string|null $image 加料商品图片
 * @property string|null $price 价格
 * @property int $inventory 库存
 * @property int $autoReplenish 自动补充库存
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $state 状态 1启用0禁用
 * @property int $catId
 * @property-read \App\Models\MaterialCat|null $category
 * @method static \Illuminate\Database\Eloquent\Builder|Material newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Material newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Material query()
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereAutoReplenish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Material whereUpdatedAt($value)
 */
	class Material extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MaterialCat
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property string $name 分类名称
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $sort
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Material[] $materialList
 * @property-read int|null $material_list_count
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat query()
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaterialCat whereUpdatedAt($value)
 */
	class MaterialCat extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Member
 *
 * @property int $id
 * @property string $nickname 用户名
 * @property string $mobile 手机号
 * @property string $password 密码
 * @property int $state 状态
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $uniacid
 * @property string|null $avatar
 * @property int $score
 * @property array|null $labelId
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $notes
 * @property int|null $sex
 * @property string|null $realname
 * @property string|null $birthday
 * @property int $groupId
 * @property int $vipId
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MemberBind[] $MemberBind
 * @property-read int|null $member_bind_count
 * @property-read \App\Models\MemberAccount|null $account
 * @property-read \App\Models\Admin\Apply|null $apply
 * @property-read string $profix
 * @property-read \App\Models\Member\Group|null $group
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MemberLabel[] $label
 * @property-read int|null $label_count
 * @property-read \App\Models\Member\Vip|null $vip
 * @method static \Illuminate\Database\Eloquent\Builder|Member members()
 * @method static \Illuminate\Database\Eloquent\Builder|Member newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Member newQuery()
 * @method static \Illuminate\Database\Query\Builder|Member onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Member query()
 * @method static \Illuminate\Database\Eloquent\Builder|Member tourists()
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereRealname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereSex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Member whereVipId($value)
 * @method static \Illuminate\Database\Query\Builder|Member withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Member withoutTrashed()
 */
	class Member extends \Eloquent implements \Tymon\JWTAuth\Contracts\JWTSubject {}
}

namespace App\Models{
/**
 * App\Models\MemberAccount
 *
 * @property int $id
 * @property int $uniacid
 * @property int $userId
 * @property string $balance
 * @property int $integral
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property string $canWithdrawalAmount 可提现金额
 * @property string $withdrawalAmount 提现中金额
 * @property string $withdrawalCompleteAmount 已提现金额
 * @property string $freezeAmount 分销达人
 * @property array $withdrawalConfig 提现账户
 * @property int $exp
 * @property-read mixed $commission
 * @property-read mixed $earnings
 * @property-read mixed $state_format
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MemberAccountLog[] $log
 * @property-read int|null $log_count
 * @property-read \App\Models\Member|null $member
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount cancel()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount money()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount pass()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount reject()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount review()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereCanWithdrawalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereExp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereFreezeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereIntegral($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereWithdrawalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereWithdrawalCompleteAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccount whereWithdrawalConfig($value)
 */
	class MemberAccount extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MemberAccountLog
 *
 * @property int $id
 * @property int $uniacid
 * @property int $userId
 * @property int $channel
 * @property int $type
 * @property string|null $notes
 * @property string $cat
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $value
 * @property int $adminId
 * @property string $atLast
 * @property int $behavior 行为
 * @property int $orderId 订单id
 * @property-read mixed $order_format
 * @property-read mixed $order_state
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereAtLast($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereBehavior($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereCat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberAccountLog whereValue($value)
 */
	class MemberAccountLog extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MemberBind
 *
 * @property int $id
 * @property int $userId 用户Id
 * @property string $type 类型:lite/wechat/ali/toutiao
 * @property string|null $unionid unionid
 * @property string $openid openid
 * @property string $nickname 昵称
 * @property string $avatar 头像
 * @property string $mobile 手机
 * @property string $data 数据
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Member|null $Member
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind query()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereOpenid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereUnionid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberBind whereUserId($value)
 */
	class MemberBind extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MemberLabel
 *
 * @property int $id
 * @property string $title
 * @property int $sort
 * @property int $state
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $uniacid
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Member\MemberLabelIds[] $member
 * @property-read int|null $member_count
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel query()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabel whereUpdatedAt($value)
 */
	class MemberLabel extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MemberSubscribe
 *
 * @property int $id
 * @property string $openId
 * @property string $unionid
 * @property int $subscribe
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe query()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe whereOpenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe whereSubscribe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe whereUnionid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberSubscribe whereUpdatedAt($value)
 */
	class MemberSubscribe extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MemberTakeoutAddress
 *
 * @method static \Illuminate\Database\Eloquent\Builder|MemberTakeoutAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberTakeoutAddress newQuery()
 * @method static \Illuminate\Database\Query\Builder|MemberTakeoutAddress onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberTakeoutAddress query()
 * @method static \Illuminate\Database\Query\Builder|MemberTakeoutAddress withTrashed()
 * @method static \Illuminate\Database\Query\Builder|MemberTakeoutAddress withoutTrashed()
 */
	class MemberTakeoutAddress extends \Eloquent {}
}

namespace App\Models\Member{
/**
 * App\Models\Member\Address
 *
 * @property int $id
 * @property int $uniacid 平台ID
 * @property int $userId 用户ID
 * @property string $address 用户ID
 * @property string $contact 联系人
 * @property string $lat 用户ID
 * @property string $lng 用户ID
 * @property string $mobile 手机号
 * @property int $isDefault 默认
 * @property string $call 称呼
 * @property string $label 标签
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array|null $regionId 区域id
 * @property string|null $description 区域id
 * @property-read mixed $region_format
 * @method static \Illuminate\Database\Eloquent\Builder|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Address newQuery()
 * @method static \Illuminate\Database\Query\Builder|Address onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Address query()
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereCall($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|Address withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Address withoutTrashed()
 */
	class Address extends \Eloquent {}
}

namespace App\Models\Member{
/**
 * App\Models\Member\Group
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $uniacid
 * @property int $sort
 * @property-read \App\Models\Member|null $member
 * @method static \Illuminate\Database\Eloquent\Builder|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereUpdatedAt($value)
 */
	class Group extends \Eloquent {}
}

namespace App\Models\Member{
/**
 * App\Models\Member\MemberLabelIds
 *
 * @property int $id
 * @property int $userId
 * @property int $labelId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds query()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MemberLabelIds whereUserId($value)
 */
	class MemberLabelIds extends \Eloquent {}
}

namespace App\Models\Member{
/**
 * App\Models\Member\Vip
 *
 * @property int $id id
 * @property int $uniacid 店铺id
 * @property int $level 等级
 * @property string $name 等级名称
 * @property int $styleSwitch 卡片样式 0默认 1 自定义
 * @property string|null $style 样式值
 * @property int $exp 所需成长值
 * @property int $balanceSwitch 余额赠送开关
 * @property string $balance 赠送余额
 * @property int $integralSwitch 积分赠送开关
 * @property int $integral 赠送积分
 * @property int $discountSwitch 折扣开关
 * @property string $discount 折扣
 * @property int $integralMultiplierSwitch 积分倍率开关
 * @property string $integralMultiplier 倍率
 * @property int $freeMailSwitch 包邮开关
 * @property string $freeMailLimit 包邮门槛
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Member[] $member
 * @property-read int|null $member_count
 * @method static \Illuminate\Database\Eloquent\Builder|Vip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vip query()
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereBalanceSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereDiscountSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereExp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereFreeMailLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereFreeMailSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereIntegral($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereIntegralMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereIntegralMultiplierSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereIntegralSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereStyleSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vip whereUpdatedAt($value)
 */
	class Vip extends \Eloquent {}
}

namespace App\Models\Member{
/**
 * App\Models\Member\VipPower
 *
 * @property int $id
 * @property int $sort 排序
 * @property string|null $icon 图标
 * @property string $name 名称
 * @property string $showName 展示名称
 * @property string $desc 简介
 * @property int $state 状态0关闭1开启
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $uniacid
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower query()
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereShowName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipPower whereUpdatedAt($value)
 */
	class VipPower extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Menu
 *
 * @property int $id
 * @property int $pid 父ID
 * @property string $name 菜单名称
 * @property string $path path
 * @property string $meta 扩展属性
 * @property string $component 链接
 * @property int $is_type 类型0 后台 1商家
 * @property int $is_sort 排序
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|Menu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu newQuery()
 * @method static \Illuminate\Database\Query\Builder|Menu onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu query()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereComponent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereIsSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereIsType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Menu withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Menu withoutTrashed()
 */
	class Menu extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MiniPrivacysetting
 *
 * @property int $id
 * @property int $uniacid
 * @property array $data
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniPrivacysetting whereUpdatedAt($value)
 */
	class MiniPrivacysetting extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MiniVersion
 *
 * @property int $id
 * @property string $appid appid
 * @property string $version 版本
 * @property string $template_id 模板id
 * @property string $desc 描述
 * @property int $state 状态
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $commit_time
 * @property int|null $auditid
 * @property string|null $audit_time
 * @property string|null $audit_ok_time
 * @property string|null $reason
 * @property string|null $screenshot
 * @property string|null $autoRelease
 * @property string|null $release_time
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereAppid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereAuditOkTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereAuditTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereAuditid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereAutoRelease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereCommitTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereReleaseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereScreenshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniVersion whereVersion($value)
 */
	class MiniVersion extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\News
 *
 * @property int $id
 * @property int $sort 排序
 * @property int $type 1图片分类
 * @property string $title 分类名称
 * @property string $content 内容
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $subTitle
 * @property string|null $user
 * @property int $state
 * @method static \Illuminate\Database\Eloquent\Builder|News newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|News newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|News query()
 * @method static \Illuminate\Database\Eloquent\Builder|News whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereSubTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereUser($value)
 */
	class News extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OpenWecahtExtJson
 *
 * @property int $id
 * @property string $version 版本
 * @property array $extJson 版本:0无插件版，1直播版
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson query()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson whereExtJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtExtJson whereVersion($value)
 */
	class OpenWecahtExtJson extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OpenWecahtVersion
 *
 * @property int $id
 * @property string $version 版本
 * @property string $template_id 模板id
 * @property string $desc 描述
 * @property string $release_time 创建时间
 * @property string $extJson 描述
 * @property int $type 版本:0无插件版，1直播版
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereExtJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereReleaseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWecahtVersion whereVersion($value)
 */
	class OpenWecahtVersion extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OpenWechatAuth
 *
 * @property int $id
 * @property string $authorizer_appid 授权小程序id
 * @property string $authorizer_access_token 授权小程序access_token
 * @property string $authorizer_refresh_token 授权小程序refresh_token
 * @property string $version 授权小程序上传版本
 * @property int $expires_time 授权小程序过期时间
 * @property int $uniacid 授权平台
 * @property array $data data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $user_name 原始id
 * @property string $type
 * @property string|null $open_appid
 * @property array|null $func_info
 * @property array|null $miniData
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth query()
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereAuthorizerAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereAuthorizerAppid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereAuthorizerRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereExpiresTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereFuncInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereMiniData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereOpenAppid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereUserName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpenWechatAuth whereVersion($value)
 */
	class OpenWechatAuth extends \Eloquent {}
}

namespace App\Models\Order{
/**
 * App\Models\Order\OrderGoods
 *
 * @property int $id
 * @property int $userId
 * @property int $spuId
 * @property string $specMd5
 * @property array|null $attrData
 * @property int $num
 * @property string $price
 * @property string $money 商品总销售价:(商品销售价-总优惠)+ 加料金额
 * @property int $discountType
 * @property string $discountMoney 优惠金额
 * @property string $materialMoney 加料金额
 * @property string $sellMony 销售金额：销售价 * 数量 + 加料金额
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $MD5
 * @property int $uniacid
 * @property int $storeId
 * @property int $discountPice
 * @property int $discountNum
 * @property string $boxPrice 包装费单价
 * @property string $boxMoney 包装费总计
 * @property string $sellMoney 销售金额=商品销售金额+加料商品
 * @property string|null $orderSn
 * @property string|null $name
 * @property string|null $logo
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereAttrData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereBoxMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereBoxPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereDiscountMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereDiscountNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereDiscountPice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereMD5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereMaterialMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereSellMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereSellMony($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereSpecMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderGoods whereUserId($value)
 */
	class OrderGoods extends \Eloquent {}
}

namespace App\Models\Order{
/**
 * App\Models\Order\OrderIndex
 *
 * @property int $id
 * @property string $orderSn 订单编号
 * @property int $type 类型
 * @property int $payType 支付类型
 * @property int $userId 用户id
 * @property string $thirdNo 第三方订单号
 * @property int $uniacid
 * @property int $payTempId 支付模板id
 * @property int $storeId
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property int $orderId
 * @property int $score 来源
 * @property string|null $deleted_at
 * @property int $state 1待支付2已支付3已退款0已关闭
 * @property-read \App\Models\Order\TakeOutOrder|null $order
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex paid()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex unpaid()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex wherePayTempId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex wherePayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereThirdNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderIndex whereUserId($value)
 */
	class OrderIndex extends \Eloquent {}
}

namespace App\Models\Order{
/**
 * App\Models\Order\TakeOutOrder
 *
 * @property int $id
 * @property string|null $orderSn
 * @property int $uniacid
 * @property int $storeId
 * @property int $userId
 * @property string|null $contacts 联系人
 * @property array|null $address 地址
 * @property int $appointment 0即时单1预约单
 * @property int $diningType 0外卖1自提2堂食
 * @property int $scene 1外卖2店内
 * @property string $boxMoney 包装费
 * @property string $deliveryMoney 配送费
 * @property string $money 订单应付金额
 * @property string|null $serverTime 预约时间
 * @property string $sellMoney 原价
 * @property int $source 来源
 * @property int $state 0已关闭1待支付2已支付待接单3已接单制作中4制作完成待配送5配送中6已完成7申请退款8已退款
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property string $pickNo 取单号
 * @property string|null $notes 订单备注
 * @property string|null $storeNotes 商家备注
 * @property string|null $payTime 支付时间
 * @property string|null $receiveTime 接单时间
 * @property string|null $deliveryTime 配送时间
 * @property string|null $completionTime 完成时间
 * @property string|null $afterSale 售后时间
 * @property string|null $afterSaleCompletion 售后完成时间
 * @property int $isBill 是否出账0未出账1已出账
 * @property string|null $billTime 出账时间
 * @property string|null $expiredTime
 * @property-read mixed $config
 * @property-read mixed $dining_type_format
 * @property-read mixed $expiration_minute
 * @property-read mixed $pay_type_format
 * @property-read mixed $pick_no
 * @property-read mixed $source_format
 * @property-read mixed $state_format
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order\OrderGoods[] $goods
 * @property-read int|null $goods_count
 * @property-read \App\Models\Order\OrderIndex|null $orderIndex
 * @property-read \App\Models\Store|null $store
 * @property-read \App\Models\Member|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder close()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder complete()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder count()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder delivery()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder making()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder refund()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder refundApply()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder unReceived()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder unpaid()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder waiting()
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereAfterSale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereAfterSaleCompletion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereAppointment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereBillTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereBoxMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereCompletionTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereContacts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereDeliveryMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereDeliveryTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereDiningType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereExpiredTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereIsBill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder wherePayTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder wherePickNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereReceiveTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereScene($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereSellMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereServerTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereStoreNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TakeOutOrder whereUserId($value)
 */
	class TakeOutOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PayConfig
 *
 * @property int $id
 * @property string $channel
 * @property string $payType
 * @property int $templateId
 * @property int $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int $uniacid
 * @property int $isDefault
 * @property int $storeId
 * @property-read mixed $data
 * @property-read \App\Models\PayTemplate|null $payTemplate
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig wherePayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayConfig whereUpdatedAt($value)
 */
	class PayConfig extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PayTemplate
 *
 * @property int $id
 * @property string|null $type
 * @property string $title
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int $uniacid
 * @property string|null $notes
 * @property int $state
 * @property int $storeId
 * @property string $channel
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayTemplate whereUpdatedAt($value)
 */
	class PayTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Plug
 *
 * @property int $id
 * @property string $baseName 默认名称
 * @property string $baseLogo 默认logo
 * @property string|null $baseDesc 默认描述
 * @property string|null $name 自定义名称
 * @property string|null $logo 自定义logo
 * @property string|null $desc 自定义描述
 * @property int $infoSwitch 商家端应用信息开关
 * @property int $paySwitch 收费开关
 * @property int $foreverSwitch 永久价开关
 * @property array|null $payData 支付套餐
 * @property int $status 状态开关
 * @property int $payType 支付渠道
 * @property \Illuminate\Support\Carbon $created_at 创建时间
 * @property \Illuminate\Support\Carbon $updated_at 更新时间
 * @property \Illuminate\Support\Carbon|null $deleted_at 软删除
 * @property string $appType 应用类型：channel,puig,service
 * @property string $appName 应用名称
 * @property int $sort
 * @method static \Illuminate\Database\Eloquent\Builder|Plug newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Plug newQuery()
 * @method static \Illuminate\Database\Query\Builder|Plug onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Plug query()
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereAppName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereAppType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereBaseDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereBaseLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereBaseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereForeverSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereInfoSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug wherePayData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug wherePaySwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug wherePayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Plug whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Plug withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Plug withoutTrashed()
 */
	class Plug extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PrintRule
 *
 * @property int $id
 * @property int $uniacid
 * @property int|null $storeId
 * @property int $printId
 * @property int $type 1前台 2后厨
 * @property array|null $scene 1外卖
 * @property array $config
 * @property string $md5Str
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property string|null $name
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule query()
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereMd5Str($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule wherePrintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereScene($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrintRule whereUpdatedAt($value)
 */
	class PrintRule extends \Eloquent {}
}

namespace App\Models\Recipe{
/**
 * App\Models\Recipe\Recipe
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property string $name 名称
 * @property string $desc 描述
 * @property int $type 类型:1外卖2堂食
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $sort
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Recipe\RecipeGoods[] $goods
 * @property-read int|null $goods_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $inStoreCats
 * @property-read int|null $in_store_cats_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Recipe\RecipeStore[] $store
 * @property-read int|null $store_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $takeoutCats
 * @property-read int|null $takeout_cats_count
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe query()
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Recipe whereUpdatedAt($value)
 */
	class Recipe extends \Eloquent {}
}

namespace App\Models\Recipe{
/**
 * App\Models\Recipe\RecipeCategory
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property int $labelId 分类标签Id
 * @property string $name 标签名称
 * @property string|null $logo logo
 * @property int $isMust 是否必须
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $notes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Recipe\RecipeGoods[] $goodsCat
 * @property-read int|null $goods_cat_count
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereIsMust($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeCategory whereUpdatedAt($value)
 */
	class RecipeCategory extends \Eloquent {}
}

namespace App\Models\Recipe{
/**
 * App\Models\Recipe\RecipeGoods
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $recipeId 模板id
 * @property int $spuId 商品ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $state
 * @property int $type 1外卖2店内
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $category
 * @property-read int|null $category_count
 * @property-read mixed $goods
 * @property-read mixed $single_spec
 * @property-read mixed $skus
 * @property-read \App\Models\Recipe\Recipe|null $recipe
 * @property-read \App\Models\GoodsSpu|null $spu
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods newQuery()
 * @method static \Illuminate\Database\Query\Builder|RecipeGoods onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoods whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|RecipeGoods withTrashed()
 * @method static \Illuminate\Database\Query\Builder|RecipeGoods withoutTrashed()
 */
	class RecipeGoods extends \Eloquent {}
}

namespace App\Models\Recipe{
/**
 * App\Models\Recipe\RecipeGoodsSku
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $recipeId 模板id
 * @property int $spuId 商品id
 * @property string|null $specMd5 specMd5
 * @property int $inventory 库存
 * @property string $price 价格
 * @property int $state 状态:1上架2下架
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $type 1外卖2店内
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\GoodsSku|null $sku
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku newQuery()
 * @method static \Illuminate\Database\Query\Builder|RecipeGoodsSku onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereSpecMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeGoodsSku whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|RecipeGoodsSku withTrashed()
 * @method static \Illuminate\Database\Query\Builder|RecipeGoodsSku withoutTrashed()
 */
	class RecipeGoodsSku extends \Eloquent {}
}

namespace App\Models\Recipe{
/**
 * App\Models\Recipe\RecipeStore
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $recipeId 模板id
 * @property int $storeId 门店id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store\StoreGoods[] $goods
 * @property-read int|null $goods_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store\StoreGoodsSku[] $goodsSkus
 * @property-read int|null $goods_skus_count
 * @property-read \App\Models\Recipe\Recipe|null $recipe
 * @property-read \App\Models\Store|null $store
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecipeStore whereUpdatedAt($value)
 */
	class RecipeStore extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\RefundOrder
 *
 * @property int $id
 * @property string|null $takeOutNo
 * @property string|null $refundNo
 * @property int $state
 * @property array $data
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder whereRefundNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefundOrder whereTakeOutNo($value)
 */
	class RefundOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Region
 *
 * @property int $id
 * @property string $name 省市区名称
 * @property int|null $level 等级
 * @property int|null $pid 上级ID
 * @property int|null $state 状态
 * @property int|null $sort 排序
 * @property string|null $pinyin 拼音
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $pinyin_prefix
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $code
 * @property-read mixed $label
 * @property-read mixed $value
 * @method static \Illuminate\Database\Eloquent\Builder|Region newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Region newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Region query()
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region wherePinyin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region wherePinyinPrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereUpdatedAt($value)
 */
	class Region extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Role
 *
 * @property int $id
 * @property string $name 角色名称
 * @property int $uniacid 平台id:0总后台
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $module 模块
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Menu[] $menus
 * @property-read int|null $menus_count
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Query\Builder|Role onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereModule($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Role withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Role withoutTrashed()
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\RoleMenu
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RoleMenu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleMenu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleMenu query()
 */
	class RoleMenu extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\RolePermission
 *
 * @method static \Illuminate\Database\Eloquent\Builder|RolePermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RolePermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RolePermission query()
 */
	class RolePermission extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Setmeal
 *
 * @property int $id
 * @property int $state 状态:上架/下架
 * @property int $sort 排序
 * @property string $title 标题
 * @property string $subtitle
 * @property string|null $desc 套餐简介
 * @property int $marketingTagSwitch 营销标签开启/关闭
 * @property string|null $marketingTag 营销标签
 * @property int $styleSwitch 营销标签开启/关闭
 * @property array $style 营销标签
 * @property int $prolongSwitch 续费开关
 * @property array $prolong 续费套餐
 * @property int $soldOutSwitch 下架后是否能续费
 * @property array $package 套餐权益
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array $money 套餐价格
 * @property int $type 套餐类型:1付费/0体验
 * @property int $day
 * @property int $smsNum 名称
 * @property int $storeNum
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Admin\Apply[] $apply
 * @property-read int|null $apply_count
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal newQuery()
 * @method static \Illuminate\Database\Query\Builder|Setmeal onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal query()
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereMarketingTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereMarketingTagSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal wherePackage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereProlong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereProlongSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereSmsNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereSoldOutSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereStoreNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereStyleSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereSubtitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setmeal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Setmeal withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Setmeal withoutTrashed()
 */
	class Setmeal extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Sms
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Sms newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sms newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sms query()
 */
	class Sms extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\SmsAccount
 *
 * @property int $id
 * @property int $uniacid
 * @property int $count
 * @property int $send_num
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $total_count
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount whereSendNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccount whereUpdatedAt($value)
 */
	class SmsAccount extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\SmsAccountLog
 *
 * @property int $id
 * @property int $uniacid
 * @property string|null $notes
 * @property string $value
 * @property int $adminId
 * @property int $type
 * @property int $atLast
 * @property int|null $behavior
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $format
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereAtLast($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereBehavior($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsAccountLog whereValue($value)
 */
	class SmsAccountLog extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\SmsLog
 *
 * @property int $id
 * @property int $uniacid
 * @property string $phone
 * @property string $data
 * @property array $res
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property int $state 名称
 * @property string|null $channel 名称
 * @property-read \App\Models\Admin\Apply|null $apply
 * @property-read mixed $channel_format
 * @property-read mixed $role_format
 * @property-read mixed $state_format
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereRes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsLog whereUpdatedAt($value)
 */
	class SmsLog extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\SmsOrder
 *
 * @property-read \App\Models\SmsAccount|null $account
 * @property-read \App\Models\Admin\Apply|null $apply
 * @property-read \App\Models\Admin|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|SmsOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsOrder query()
 */
	class SmsOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Smscombo
 *
 * @property int $id id
 * @property int $sort 排序
 * @property int $num 短信条数
 * @property float $price 价格
 * @property float $linePrice 划线价
 * @property int $state 状态0下架1上架
 * @property \Illuminate\Support\Carbon $created_at 创建时间
 * @property \Illuminate\Support\Carbon $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo query()
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo whereLinePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo whereNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Smscombo whereUpdatedAt($value)
 */
	class Smscombo extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Spec
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property string $name 标签名称
 * @property array $value 规格值
 * @property int $imgSwitch 是否展示规格图片
 * @property string|null $desc 描述
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Spec newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Spec newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Spec query()
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereImgSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Spec whereValue($value)
 */
	class Spec extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\SpuCatgorys
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $spuId 商品id
 * @property int $catId 商品分类id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys query()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuCatgorys whereUpdatedAt($value)
 */
	class SpuCatgorys extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\SpuLabels
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $spuId 商品id
 * @property int $labelId 标签id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels query()
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpuLabels whereUpdatedAt($value)
 */
	class SpuLabels extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Storage
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Storage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Storage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Storage query()
 */
	class Storage extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Store
 *
 * @property int $id
 * @property int $sort 排序
 * @property int $groupId 门店分组
 * @property string $name 门店名称
 * @property string $storeSn 门店编码
 * @property array $surroundings 环境图
 * @property array $region 省/市/区
 * @property string $address 详细地址
 * @property string|null $lat lat
 * @property string|null $lng lng
 * @property string $contact 联系人
 * @property string $mobile 联系电话
 * @property string|null $storeMobile
 * @property array|null $labelId 门店标签
 * @property int $isShowSwitch 是否显示
 * @property int $operatingStatus 经营状态
 * @property int $businessStatus 营业状态
 * @property array $businessData 营业时间
 * @property string|null $shareImg 分享图片
 * @property string|null $shareTitle 分享描述
 * @property array $businessLicense 营业执照
 * @property array $tradeLicense 行业许可证
 * @property int $takeoutSwitch 外卖开关
 * @property int $inStoreSwitch 堂食开关
 * @property int $paySwitch 买单业务
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $uniacid
 * @property int $pickupSwitch 自取开关
 * @property-read mixed $delivery
 * @property-read mixed $distance
 * @property-read mixed $store_setting
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $inStoreCats
 * @property-read int|null $in_store_cats_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StoreLabel[] $label
 * @property-read int|null $label_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Recipe\RecipeStore[] $recipeStore
 * @property-read int|null $recipe_store_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GoodsRecommend\Store[] $recommendStore
 * @property-read int|null $recommend_store_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $takeoutCats
 * @property-read int|null $takeout_cats_count
 * @method static \Illuminate\Database\Eloquent\Builder|Store business()
 * @method static \Illuminate\Database\Eloquent\Builder|Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Store newQuery()
 * @method static \Illuminate\Database\Query\Builder|Store onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Store query()
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereBusinessData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereBusinessLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereBusinessStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereInStoreSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereIsShowSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereOperatingStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store wherePaySwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store wherePickupSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereShareImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereShareTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereStoreMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereStoreSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereSurroundings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereTakeoutSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereTradeLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Store withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Store withoutTrashed()
 */
	class Store extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\StoreConfig
 *
 * @property int $id
 * @property array $data
 * @property int $storeId 平台
 * @property string $ident 标识
 * @property string $name 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereIdent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreConfig whereUpdatedAt($value)
 */
	class StoreConfig extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\StoreGroup
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property string $name 标签名称
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGroup whereUpdatedAt($value)
 */
	class StoreGroup extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\StoreLabel
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property string $name 标签名称
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreLabel whereUpdatedAt($value)
 */
	class StoreLabel extends \Eloquent {}
}

namespace App\Models\Store{
/**
 * App\Models\Store\Account
 *
 * @property int $id
 * @property int $uniacid
 * @property int $storeId
 * @property string $amount 可提现金额
 * @property string|null $withdrawalAmount 提现金额
 * @property string $withdrawalCompleteAmount 已体现金额
 * @property string $freezeAmount 冻结金额
 * @property string $refundOfAmount 退款中金额
 * @property string $refundAmount 已退款金额
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property array|null $rateConfig
 * @property array|null $withdrawalConfig
 * @property-read \App\Models\Store|null $Store
 * @property-read mixed $total_amount
 * @method static \Illuminate\Database\Eloquent\Builder|Account money()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereFreezeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereRateConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereRefundAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereRefundOfAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereWithdrawalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereWithdrawalCompleteAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereWithdrawalConfig($value)
 */
	class Account extends \Eloquent {}
}

namespace App\Models\Store{
/**
 * App\Models\Store\AccountLog
 *
 * @property int $id
 * @property int $uniacid
 * @property int $storeId
 * @property int $channel
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $value
 * @property int $adminId
 * @property string $atLast
 * @property int $type
 * @property int|null $behavior
 * @property-read mixed $format
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereAtLast($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereBehavior($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccountLog whereValue($value)
 */
	class AccountLog extends \Eloquent {}
}

namespace App\Models\Store{
/**
 * App\Models\Store\GoodsList
 *
 * @property int $id
 * @property int $uniacid
 * @property int $storeId
 * @property int $recipeId
 * @property int $spuId
 * @property string $salesTimeData
 * @property int $state
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property int $type
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $category
 * @property-read int|null $category_count
 * @property-read mixed $goods
 * @property-read mixed $single_spec
 * @property-read mixed $skus
 * @property-read \App\Models\Goods\SpuList|null $spu
 * @property-read \App\Models\Store\StoreGoodsSku|null $storeSkus
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList inventoryOff()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList offShelf()
 * @method static \Illuminate\Database\Query\Builder|GoodsList onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList shelf()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereSalesTimeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsList whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|GoodsList withTrashed()
 * @method static \Illuminate\Database\Query\Builder|GoodsList withoutTrashed()
 */
	class GoodsList extends \Eloquent {}
}

namespace App\Models\Store{
/**
 * App\Models\Store\StoreCategory
 *
 * @property int $id
 * @property int $uniacid 店铺id
 * @property int $sort 排序
 * @property int $labelId 分类标签Id
 * @property string $name 标签名称
 * @property string|null $logo logo
 * @property int $isMust 是否必须
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $notes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store\StoreGoods[] $goodsCat
 * @property-read int|null $goods_cat_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store\GoodsList[] $goodsList
 * @property-read int|null $goods_list_count
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereIsMust($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereLabelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreCategory whereUpdatedAt($value)
 */
	class StoreCategory extends \Eloquent {}
}

namespace App\Models\Store{
/**
 * App\Models\Store\StoreGoods
 *
 * @property int $id
 * @property int $uniacid
 * @property int $storeId
 * @property int $recipeId
 * @property int $spuId
 * @property string $salesTimeData
 * @property int $state
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property int $type
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpuCatgorys[] $category
 * @property-read int|null $category_count
 * @property-read mixed $goods
 * @property-read mixed $single_spec
 * @property-read mixed $skus
 * @property-read \App\Models\GoodsSpu|null $spu
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Store\StoreGoodsSku[] $storeSkus
 * @property-read int|null $store_skus_count
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods inventoryOff()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods offShelf()
 * @method static \Illuminate\Database\Query\Builder|StoreGoods onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods shelf()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereSalesTimeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoods whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|StoreGoods withTrashed()
 * @method static \Illuminate\Database\Query\Builder|StoreGoods withoutTrashed()
 */
	class StoreGoods extends \Eloquent {}
}

namespace App\Models\Store{
/**
 * App\Models\Store\StoreGoodsSku
 *
 * @property int $id
 * @property int|null $uniacid
 * @property int $recipeId
 * @property int $storeId
 * @property int $spuId
 * @property string $specMd5
 * @property int $inventory
 * @property string $price
 * @property int $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $type
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $surplusInventory
 * @property int $dayFilling 次日制满
 * @property-read \App\Models\GoodsSku|null $sku
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku newQuery()
 * @method static \Illuminate\Database\Query\Builder|StoreGoodsSku onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereDayFilling($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereSpecMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereSurplusInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreGoodsSku whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|StoreGoodsSku withTrashed()
 * @method static \Illuminate\Database\Query\Builder|StoreGoodsSku withoutTrashed()
 */
	class StoreGoodsSku extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\StoredValue
 *
 * @property int $id
 * @property string $name
 * @property string $amount
 * @property int $integralGive
 * @property float $balanceGive
 * @property int $levelGive
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $deleted_at
 * @property int $integralSwitch
 * @property int $balanceSwitch
 * @property int $levelSwitch
 * @property int $uniacid
 * @property int $sort
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereBalanceGive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereBalanceSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereIntegralGive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereIntegralSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereLevelGive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereLevelSwitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoredValue whereUpdatedAt($value)
 */
	class StoredValue extends \Eloquent {}
}

namespace App\Models\TakeOut{
/**
 * App\Models\TakeOut\Cart
 *
 * @property int $id
 * @property int $userId
 * @property int $spuId
 * @property string $specMd5
 * @property array|null $attrData
 * @property int $num
 * @property string $price
 * @property string $money 商品总销售价:(商品销售价-总优惠)+ 加料金额
 * @property int $discountType
 * @property string $discountMoney 优惠金额
 * @property string $materialMoney 加料金额
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $MD5
 * @property int $uniacid
 * @property int $storeId
 * @property int $discountPice
 * @property int $discountNum
 * @property string $boxPrice 包装费单价
 * @property string $boxMoney 包装费总计
 * @property string $sellMoney 销售金额=商品销售金额+加料商品
 * @property-read \App\Models\Goods\SpuList|null $goods
 * @method static \Illuminate\Database\Eloquent\Builder|Cart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cart query()
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereAttrData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereBoxMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereBoxPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereDiscountMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereDiscountNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereDiscountPice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereMD5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereMaterialMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereSellMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereSpecMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereSpuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cart whereUserId($value)
 */
	class Cart extends \Eloquent {}
}

namespace App\Models\TakeOut{
/**
 * App\Models\TakeOut\CartList
 *
 * @property-read void $box_money
 * @property-read void $delivery_money
 * @property-read void $goods_count
 * @property-read void $money
 * @property-read void $sell_money
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TakeOut\Cart[] $goodsList
 * @property-read int|null $goods_list_count
 * @property-read \App\Models\Store|null $store
 * @method static \Illuminate\Database\Eloquent\Builder|CartList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CartList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CartList query()
 */
	class CartList extends \Eloquent {}
}

namespace App\Models\TakeOut{
/**
 * App\Models\TakeOut\Checkout
 *
 * @property-read mixed $address
 * @property-read mixed $address_id
 * @property-read mixed $address_list
 * @property-read mixed $appointment
 * @property-read mixed $box_money
 * @property-read mixed $car_list
 * @property-read mixed $contacts
 * @property-read mixed $delivery
 * @property-read mixed $delivery_money
 * @property-read mixed $goods_list
 * @property-read mixed $interval
 * @property-read mixed $money
 * @property-read mixed $reservation_day
 * @property-read mixed $reservation_time
 * @property-read mixed $scene
 * @property-read mixed $sell_money
 * @property-read mixed $store
 * @property-read mixed $time_arr
 * @method static \Illuminate\Database\Eloquent\Builder|Checkout newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Checkout newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Checkout query()
 */
	class Checkout extends \Eloquent {}
}

namespace App\Models\TakeOut{
/**
 * App\Models\TakeOut\Delivery
 *
 * @property-read mixed $address
 * @property-read mixed $circle
 * @property-read mixed $distance
 * @property-read mixed $fee
 * @property-read mixed $km
 * @property-read mixed $minutes
 * @property-read mixed $money
 * @property-read mixed $msg
 * @property-read mixed $point
 * @property-read mixed $price_type
 * @property-read mixed $rule
 * @property-read mixed $start_count
 * @property-read mixed $start_money
 * @property-read mixed $start_rule
 * @property-read mixed $state
 * @property-read \App\Models\Store|null $store
 * @property-read \App\Models\Delivery\Store|null $storeRule
 * @method static \Illuminate\Database\Eloquent\Builder|Delivery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Delivery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Delivery query()
 */
	class Delivery extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Tester
 *
 * @property int $id
 * @property string|null $wechatid 微信号
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $appid
 * @method static \Illuminate\Database\Eloquent\Builder|Tester newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tester newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tester query()
 * @method static \Illuminate\Database\Eloquent\Builder|Tester whereAppid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tester whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tester whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tester whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tester whereWechatid($value)
 */
	class Tester extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Visit
 *
 * @property int $id
 * @property int $uniacid
 * @property string $type
 * @property string $model
 * @property string $route
 * @property string $ip
 * @property string $request_type
 * @property int $userId 用户Id
 * @property string $post_str
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $method
 * @property \Illuminate\Support\Carbon $updated_at
 * @property int $shop_id 门店ID
 * @method static \Illuminate\Database\Eloquent\Builder|Visit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Visit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Visit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Visit stateCount()
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit wherePostStr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereRequestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereUniacid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Visit whereUserId($value)
 */
	class Visit extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\WeCom
 *
 * @method static \Illuminate\Database\Eloquent\Builder|WeCom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WeCom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WeCom query()
 */
	class WeCom extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\WechatAttachment
 *
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAttachment query()
 */
	class WechatAttachment extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\WechatList
 *
 * @method static \Illuminate\Database\Eloquent\Builder|WechatList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatList query()
 */
	class WechatList extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\WechatMenu
 *
 * @method static \Illuminate\Database\Eloquent\Builder|WechatMenu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatMenu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatMenu query()
 */
	class WechatMenu extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\WechatReply
 *
 * @method static \Illuminate\Database\Eloquent\Builder|WechatReply newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatReply newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatReply query()
 */
	class WechatReply extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Ztkj
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Ztkj newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ztkj newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ztkj query()
 */
	class Ztkj extends \Eloquent {}
}

