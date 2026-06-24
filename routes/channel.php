<?php

use App\Http\Controllers\Channel\AdController;
use App\Http\Controllers\Channel\ApplyController;
use App\Http\Controllers\Channel\ConfigController;
use App\Http\Controllers\Channel\FileController;
use App\Http\Controllers\Channel\GoodsCatController;
use App\Http\Controllers\Channel\GoodsCatLabelController;
use App\Http\Controllers\Channel\LoginController;
use App\Http\Controllers\Channel\MiniUploadController;
use App\Http\Controllers\Channel\MusterController;
use App\Http\Controllers\Channel\OfficialController;
use App\Http\Controllers\Channel\OpenWechatController;
use App\Http\Controllers\Channel\PayConfigController;
use App\Http\Controllers\Channel\PayTemplateController;
use App\Http\Controllers\Channel\PlugController;
use App\Http\Controllers\Channel\SmsController;
use App\Http\Controllers\Channel\SpecController;
use App\Http\Controllers\Channel\AttrController;
use App\Http\Controllers\Channel\Delivery\ChannelController;
use App\Http\Controllers\Channel\Delivery\MaiyatianController;
use App\Http\Controllers\Channel\Delivery\OrderController as DeliveryOrderController;
use App\Http\Controllers\Channel\Delivery\RuleController;
use App\Http\Controllers\Channel\Delivery\StoreController as DeliveryStoreController;
use App\Http\Controllers\Channel\Delivery\WaiSongBangController;
use App\Http\Controllers\Channel\Discount\CouponController;
use App\Http\Controllers\Channel\Discount\CouponReceiveController;
use App\Http\Controllers\Channel\Discount\ExchangeCodeController;
use App\Http\Controllers\Channel\GoodsLabelController;
use App\Http\Controllers\Channel\GoodsMarkController;
use App\Http\Controllers\Channel\MaterialController;
use App\Http\Controllers\Channel\StoreController;
use App\Http\Controllers\Channel\UserController;
use App\Http\Controllers\Channel\WechatController;
use App\Http\Controllers\Channel\WechatReplyController;
use App\Http\Controllers\Channel\Delivery\QldController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Channel\WithdrawalController;
use App\Http\Controllers\Channel\StoreLabelController;
use App\Http\Controllers\Channel\StoreGroupController;
use App\Http\Controllers\Channel\DragController;
use App\Http\Controllers\Channel\GoodsController;
use App\Http\Controllers\Channel\GoodsRecommend\RecommendController;
use App\Http\Controllers\Channel\GoodsRecommend\StoreController as GoodsRecommendStoreController;
use App\Http\Controllers\Channel\GoodsUnitController;
use App\Http\Controllers\Channel\HandleLogController;
use App\Http\Controllers\Channel\ImportController;
use App\Http\Controllers\Channel\MaterialCatController;
use App\Http\Controllers\Channel\Member\AccountLogController;
use App\Http\Controllers\Channel\Member\GroupController;
use App\Http\Controllers\Channel\Member\JobController;
use App\Http\Controllers\Channel\Member\LabelController;
use App\Http\Controllers\Channel\Member\MemberController;
use App\Http\Controllers\Channel\Member\VipController;
use App\Http\Controllers\Channel\Member\VipPowerController;
use App\Http\Controllers\Channel\MessageConfigController;
use App\Http\Controllers\Channel\Mini\DomainController;
use App\Http\Controllers\Channel\OrderController;
use App\Http\Controllers\Channel\Prints\HardwareController;
use App\Http\Controllers\Channel\Prints\RuleController as PrintsRuleController;
use App\Http\Controllers\Channel\Recipe\GoodsController as RecipeGoodsController;
use App\Http\Controllers\Channel\Recipe\RecipeController;
use App\Http\Controllers\Channel\Recipe\StoreController as RecipeStoreController;
use App\Http\Controllers\Channel\Store\GoodsController as StoreGoodsController;
use App\Http\Controllers\Channel\StoreConfigController;
use App\Http\Controllers\Channel\StoredValueController;
use App\Http\Controllers\Channel\NotifyController;
use App\Http\Controllers\Channel\PrinterLogController;
use App\Http\Controllers\Channel\Salesclerk\AdminController;
use App\Http\Controllers\Channel\Salesclerk\MenuController;
use App\Http\Controllers\Channel\Salesclerk\RoleController;
use App\Http\Controllers\Channel\SmsAccountController;
use App\Http\Controllers\Channel\StatisticsController;
use App\Http\Controllers\Channel\Store\AccountController;
use App\Http\Controllers\Channel\Store\NoticeController;
use App\Http\Controllers\Channel\Discount\FullSubController;
use App\Http\Controllers\Channel\Discount\GiftBigController;
use App\Http\Controllers\Channel\Discount\NewSubController;
use App\Http\Controllers\Channel\Discount\OrderCollectController;
use App\Http\Controllers\Channel\Discount\PayGiftController;
use App\Http\Controllers\Channel\Discount\WindowCouponController;
use App\Http\Controllers\Channel\HelpersController;
use App\Http\Controllers\Channel\StoredValueOrderController;
use App\Http\Controllers\Channel\PointsMallClassificationController;
use App\Http\Controllers\Channel\PointsMallController;
use App\Http\Controllers\Channel\StorePayConfigController;
use App\Http\Controllers\Channel\AdvertisementController;
use App\Http\Controllers\Channel\Table\AreaController;
use App\Http\Controllers\Channel\AttrValueController;
use App\Http\Controllers\Channel\CostomPayController;
use App\Http\Controllers\Channel\CouponPackOrderController;
use App\Http\Controllers\Channel\Discount\CouponActivityController;
use App\Http\Controllers\Channel\Discount\CouponPackController;
use App\Http\Controllers\Channel\Discount\EquityCardController;
use App\Http\Controllers\Channel\Discount\GooodsDiscountController;
use App\Http\Controllers\Channel\Discount\PartnerController;
use App\Http\Controllers\Channel\Discount\TradeInGoodsController;
use App\Http\Controllers\Channel\Discount\VipCardController;
use App\Http\Controllers\Channel\Discount\VipGoodsController;
use App\Http\Controllers\Channel\Discount\WordCouponController;
use App\Http\Controllers\Channel\Drinks\DrinksController;
use App\Http\Controllers\Channel\Drinks\LogController;
use App\Http\Controllers\Channel\Drinks\OrderController as DrinksOrderController;
use App\Http\Controllers\Channel\Drinks\StorageController;
use App\Http\Controllers\Channel\Finance\BalanceController;
use App\Http\Controllers\Channel\Finance\OlineOrderController;
use App\Http\Controllers\Channel\Finance\ProfitController;
use App\Http\Controllers\Channel\Handover\HandoverController;
use App\Http\Controllers\Channel\InStore\CartController as InStoreCartController;
use App\Http\Controllers\Channel\InStore\CheckoutController;
use App\Http\Controllers\Channel\InStore\FreezeOrderController;
use App\Http\Controllers\Channel\InStore\GoodsController as InStoreGoodsController;
use App\Http\Controllers\Channel\Mini\JumpQrcodeController;
use App\Http\Controllers\Channel\Mini\RegisterController;
use App\Http\Controllers\Channel\PersonPayOrderController;
use App\Http\Controllers\Channel\SpecValueController;
use App\Http\Controllers\Channel\Table\TableController;
use App\Http\Controllers\Channel\Table\TypeController;
use App\Http\Controllers\Channel\PointSignController;
use App\Http\Controllers\Channel\SignListController;
use App\Http\Controllers\Channel\InStore\OrderController as InStoreOrderController;
use App\Http\Controllers\Channel\Mini\PathController;
use App\Http\Controllers\Channel\TakeScreenController;
use App\Http\Controllers\Channel\Prints\PrintTemplateController;
use App\Http\Controllers\Channel\Prints\PrintStoreTemplateController;
use App\Http\Controllers\Channel\InStore\TableController as InStoreTableController;
use App\Http\Controllers\Channel\Mini\ApplySetOrderController;
use App\Http\Controllers\Channel\Mini\OrderPathInfoController;
use App\Http\Controllers\Channel\OldWithNew\ActivityController;
use App\Http\Controllers\Channel\PointsMallOrderController;
use App\Http\Controllers\Channel\Table\ServerController;
use App\Http\Controllers\Channel\VoiceMessageController;
use App\Http\Controllers\Channel\QueuingUpController;
use App\Http\Controllers\Channel\TableReserveOrderController;
use App\Http\Controllers\Channel\UserWithdrawalController;
use App\Http\Resources\Admin\HandeLog\HandeListCollection;
use App\Models\Admin\HandleLog;
use App\Models\AttrValue;
use App\Models\ExchangeCode\ExchangeCode;
use App\Models\FullSub\FullSub;
use App\Models\GoodsMark;
use App\Models\InStore\FreezeOrder;
use App\Models\MaterialCat;
use App\Models\Mini\Register;
use App\Models\OldWithNew\Activity;
use App\Models\Order\TakeScreen;
use App\Models\PersionPayOrder;
use App\Models\Store\Account;
use App\Models\StoredValueOrder;
use App\Models\WindowCoupon\CouponReceive;
use App\Http\Controllers\Channel\Discount\StorePartnerController;
use App\Http\Controllers\Channel\LuckyWheelRecordController;
use App\Http\Controllers\Channel\LuckyWheelRewardController;
use App\Http\Controllers\Channel\LuckyWheelController;
use App\Http\Controllers\Channel\AwakenController;
use App\Http\Controllers\Channel\GoodsLogController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


//通知回调
Route::prefix('notify')->group(function () {
    Route::controller(NotifyController::class)->group(function () {
        Route::any('/make/{uniacid}', "make")->name('notify.make'); //获取菜单;
        Route::any('/maiyatian', "maiyatian")->name('notify.maiyatian'); //获取菜单;
        Route::any('/waisongbang', "waisongbang")->name('notify.waisongbang'); //获取菜单;
        Route::any('/reallySavesMoney', "reallySavesMoney")->name('notify.reallySavesMoney'); //获取菜单;
        Route::any('/qulaida', "qulaida")->name('notify.qulaida');
        Route::any('/shunfeng', "shunfeng")->name('notify.shunfeng');
        Route::any('/fengniao', "fengniao")->name('notify.fengniao');
        Route::any('/uu', "uu")->name('notify.uu');
        Route::any('/dd', "dd")->name('notify.dd');
        Route::any('/shansong', "shansong")->name('notify.shansong');
        Route::any('/kuaidi', "kuaidi")->name('notify.kuaidi');
    });
});
Route::middleware(['checkDoman'])->group(function () {
    Route::controller(SmsAccountController::class)->group(function () {
        Route::get('smsAccount', "index")->name('sms.list');
        Route::prefix('smsAccount')->group(function () {
            Route::post('/pay', "pay")->name('sms.pay');
            Route::get('/store', "payList")->name('sms.store');
            Route::get('/log', "log")->name('sms.log');
            Route::get('/state', "state")->name('sms.state');
            Route::get('/order', "order")->name('smsPay.order');
            Route::get('/smsLog', "smsLog")->name('sms.smsLog');
        });
    });

    Route::controller(LoginController::class)->group(function () {
        Route::post('/login', "login")->name('login');
        Route::post('/mobileLogin', "mobileLogin")->name('mobileLogin');
        Route::post('regiset', 'regiset')->name('regiset');
        Route::get('/config', "config")->name('login.config');
        Route::get('/login/getOpenWechat', 'getOpenWechat');
        Route::get('/login/wechatLoginState', 'wechatLoginState')->name('wechatLoginState');
        Route::post('/login/wechatBind', 'WechatBind')->name('wechatBind');
        Route::any('/login/wechatLogin/{channel}', 'wechatLogin')->name('wechatLogin');
        Route::post('/checkCode', "checkCode")->name('checkCode');
        Route::post('/retrievePassword', "retrievePassword")->name('retrievePassword'); //找回密码
    });

    //个人中心
    Route::controller(UserController::class)->group(function () {
        Route::get('/wxBind/{userId}', "wxBind")->name('wxBind'); //获取菜单;
    });

    //配置
    Route::controller(ConfigController::class)->group(function () {
        Route::get('/systemConfig', "system")->name('systemConfig');
        Route::get('/downTemplate', "downTemplate")->name('downTemplate');
        Route::get('/authCode', "authCode")->name('authCode');
    });
    //微信扫码登录
    Route::controller(SmsController::class)->group(function () {
        Route::post('/sms/retrieve', "retrieve")->name('retrieveSms'); //修改密码
        Route::post('/sms/register', "register")->name('register'); //注册
        Route::post('/sms/login', "login")->name('sms.login'); //注册
        Route::post('/sms/login-sms', "loginSms")->name('login-sms'); //注册
        Route::post('/sms/smsSend', "smsSend")->name('smsSend'); //注册
    });

    Route::group(['middleware' => ['jwt.channel', 'onlyLogin']], function () {
        Route::controller(LoginController::class)->group(function () {
            Route::post('/logout', "logout")->name('logout');
            Route::post('/channelLogin', "channellogin")->name('channellogin');
            Route::post('login/enterPosition', 'enterPosition')->name('enterPosition');
        });

        //获取插件列表
        Route::prefix('admins')->group(function () {
            Route::controller(AdminController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('state'); //获取菜单;
            });
        });

        //配置
        Route::controller(ConfigController::class)->group(function () {
            Route::get('/config', "index")->name('config.get'); //获取菜单;
            Route::post('/config', "store")->name('config.create'); //设置配置;
            Route::put('/config/{ident}', "update")->name('config.update'); //更新配置;
            Route::delete('/config/{ident}', "destroy")->name('config.destroy'); //删除配置;
            Route::post('/config/audition', "audition")->name('audition');
            Route::get('/config/followCode', "followCode")->name('followCode');
            Route::get('/config/getFollowList', "getFollowList")->name('getFollowList');
            Route::post('/config/delFollow', "delFollow")->name('delFollow');
            Route::post('/config/modifyName', "modifyName")->name('modifyName');
        });


        //个人中心
        Route::controller(UserController::class)->group(function () {
            Route::post('/changePassword', "changePassword")->name('changePassword'); //修改密码
            Route::get('/profix', "index")->name('profix.get'); //获取个人信息;
            Route::post('/profix', "profix")->name('profix.update'); //获取个人信息;
            Route::get('/loadMenus', "loadMenus")->name('loadMenus'); //获取菜单;
            Route::get('/customerService', "customerService")->name('customerService'); //获取菜单;
            Route::get('/changePasswordSms', "changePasswordSms")->name('changePasswordSms'); //获取菜单;
            Route::get('/getWx', "getWx")->name('getWx'); //获取菜单;
            Route::get('/checkApply', "checkApply")->name('checkApply'); //获取菜单;
        });

        //小程序上传
        // Route::post('miniUpload/getEdition', "getEdition")->name('miniUpload.getEdition');
        // Route::post('miniUpload/getLoginCode', "getLoginCode")->name('miniUpload.getLoginCode');
        // Route::post('miniUpload/getLoginStatus', "getLoginStatus")->name('miniUpload.getLoginStatus');
        // Route::post('miniUpload/preview', "preview")->name('miniUpload.preview');
        // Route::post('miniUpload/config', "config")->name('miniUpload.config');
        // Route::post('miniUpload/returnUrl', "returnUrl")->name('miniUpload.returnUrl');
        // Route::post('miniUpload/callbackurl ', "callbackurl")->name('miniUpload.callbackurl');

        //平台
        Route::controller(ApplyController::class)->group(function () {
            Route::prefix('apply')->group(function () {
                Route::get('/survey', 'survey')->name('apply.surver'); //平台概况
                Route::get('/recycle', 'recycle')->name('apply.recycle'); //回收站
                Route::post('/state/{apply}', 'state')->name('apply.state'); //拉黑
                Route::delete('/del/{apply}', 'del')->name('apply.del'); //回收站删除
                Route::post('/restore/{apply}', 'restore')->name('apply.restore'); //回收站恢复
                Route::get('/ceshi', 'ceshi')->name('apply.ceshi'); //回收站恢复
                Route::get('/plugins', "plugins")->name('apply.plugins'); //获取插件;
                Route::post('/top/{id}', "top")->name('apply.top'); //获取插件;
            });
        });
        //获取授权url

        Route::prefix('mini')->group(function () {
            Route::controller(OpenWechatController::class)->group(function () {
                Route::get('getAuthorizationUrl', "getAuthorizationUrl")->name('openWechat.mimi.getAuthorizationUrl'); //获取个人信息;
                Route::get('config', "config")->name('openWechat.mini.config'); //更新配置;
                Route::get('switch', "switch")->name('openWechat.mini.switch'); //更新配置;
            });
            Route::prefix('domain')->group(function () {
                Route::controller(DomainController::class)->group(function () {
                    Route::get('file', "file")->name('mini.domain.file'); //更新配置;
                    Route::post('getDomainList', "getDomainList")->name('mini.domain.getDomainList'); //更新配置;
                    Route::post('setDomain', "setDomain")->name('mini.domain.setDomain'); //更新配置;
                });
            });
            Route::prefix('jumpQrCode')->group(function () {
                Route::controller(JumpQrcodeController::class)->group(function () {
                    Route::post('publish', "publish")->name('mini.jumpQrCode.publish'); //更新配置;
                    Route::delete('', "destroy")->name('mini.jumpQrCode.destroy'); //更新配置;
                });
            });
            Route::prefix('path')->group(function () {
                Route::controller(PathController::class)->group(function () {
                    Route::get('qrcode/{id}', "qrcode")->name('mini.path.qrCode'); //更新配置;
                });
            });
            Route::apiResources([
                "domain" => DomainController::class,
                'register' => RegisterController::class,
                'jumpQrCode' => JumpQrcodeController::class,
                'path' => PathController::class,
                'orderPathInfo' => OrderPathInfoController::class,
            ]);
        });

        Route::controller(MiniUploadController::class)->group(function () {
            Route::prefix('mini-upload')->group(function () {
                Route::get('version', "version")->name('miniUpload.version');
                Route::get('refreshAudit', "refreshAudit")->name('miniUpload.refreshAudit');
                Route::post('miniData', "miniData")->name('miniUpload.miniData');
                Route::post('upload', "upload")->name('miniUpload.upload');
                Route::get('code-list', "codeList")->name('miniUpload.codeList');
                Route::post('commit', "commit")->name('miniUpload.commit'); //提交小程序
                Route::get('qrcode', "get_qrcode")->name('miniUpload.qrcode'); //体验二维码
                Route::get('submit-audit', "submitAudit")->name('miniUpload.submitAudit'); //提交审核
                Route::get('undocodeaudit', "undocodeaudit")->name('miniUpload.undocodeaudit'); //撤销审核
                Route::post('release', "release")->name('miniUpload.release'); //发布审核版本小程序
                Route::post('bind-tester', "bind_tester")->name('miniUpload.bind_testre'); //发布审核版本小程序
                Route::post('unbind-testre', "unbind_tester")->name('miniUpload.unbind_tester'); //发布审核版本小程序
                Route::get('tester-list', "tester_list")->name('miniUpload.tester-list'); //发布审核版本小程序
                Route::get('mini-upload', "index")->name('miniUpload.list'); //发布审核版本小程序
                Route::get('getprivacysetting', "getprivacysetting")->name('miniUpload.getprivacysetting'); //发布审核版本小程序
                Route::post('setprivacysetting', "setprivacysetting")->name('miniUpload.setprivacysetting'); //发布审核版本小程序
                Route::post('speedupCodeAudit', "speedupCodeAudit")->name('miniUpload.speedupCodeAudit'); //加急审核
            });
        });

        Route::controller(OfficialController::class)->group(function () {
            Route::prefix('official')->group(function () {
                Route::get('version', "version")->name('official.version'); //获取公众号信息
            });
        });



        Route::controller(MusterController::class)->group(function () {
            Route::prefix('muster')->group(function () {
                Route::post('pay', "pay")->name('muster.pay'); //获取公众号信息
                Route::get('payState', "payState")->name('muster.payState'); //获取公众号信息
                Route::post('experience', "experience")->name('muster.experience'); //获取公众号信息
                Route::get('renewal', "renewal")->name('muster.renewal'); //获取公众号信息
            });
        });




        Route::controller(PayTemplateController::class)->group(function () {
            Route::prefix('payTemplate')->group(function () {
                Route::post('state/{payTemplate}', "state")->name('payTemplate.state'); //获取公众号信息
                Route::get('pickList', 'pickList')->name('payTemplate.pickList');
                Route::get('wxList', 'wxList')->name('payTemplate.wxList');
                Route::post('withdrawal', "withdrawal")->name('payTemplate.withdrawal'); //获取公众号信息
            });
        });






        Route::prefix('file')->group(function () {
            Route::controller(FileController::class)->group(function () {
                Route::post('upload', 'upload');
                Route::post('uploadBase64', 'uploadBase64');
                Route::any('ceshi', 'ceshi');
                Route::post('saveCategory', 'saveCategory');
                Route::get('getCategory', 'getCategory');
                Route::post('moveFile', 'moveFile');
                Route::get('getPicture', 'getPicture');
                Route::post('delPictureCategory', 'delPictureCategory');
                Route::post('delPicture', 'delPicture');
                Route::post('recyclePicture', 'recyclePicture');
                Route::post('delRecyclePicture', 'delRecyclePicture');
                Route::post('editPicture', 'editPicture');
                Route::post('changeCategory', 'changeCategory');
            });
        });


        Route::controller(WithdrawalController::class)->group(function () {
            Route::prefix('withdrawal')->group(function () {
                Route::post('/online/{withdrawal}', "online")->name('withdrawal.online');
                Route::post('/offline/{withdrawal}', "offline")->name('withdrawal.offline');
                Route::post('/reject/{withdrawal}', "reject")->name('withdrawal.reject');
                Route::get('/WithdrawalExport', "WithdrawalExport")->name('withdrawal.WithdrawalExport');
            });
        });
        Route::prefix('store')->group(function () {
            Route::controller(StoreController::class)->group(function () {
                Route::post('/switch/{type}/{store}', "switch")->name('store.switch');
                Route::post('/businessStatus/{store}', "businessStatus")->name('store.businessStatus');
                Route::post('/qrcode/{type}', "qrcode")->name('store.qrcode');
                Route::post('/copyStore', "copyStore")->name('store.copyStore');
                Route::post('/placeSearch', "placeSearch")->name('store.placeSearch');
            });
            Route::prefix('goods')->group(function () {
                Route::controller(StoreGoodsController::class)->group(function () {
                    Route::get('/recipe', "recipe")->name('store.goods.recipe'); //商品分类列表
                    Route::get('/category/{store}', "category")->name('store.goods.category'); //商品分类列表
                    Route::get('/{store}', "index")->name('store.goods.index'); //商品列表
                    Route::put('/{store}', "update")->name('store.goods.update'); //编辑商品
                    Route::delete('/{store}', "destroy")->name('store.goods.destroy'); //下架商品
                    Route::post('/{store}', "restore")->name('channel.store.goods.restore'); //上架商品
                    Route::post('/outofStock/{store}', "outofStock")->name('store.goods.outofStock'); //上架商品
                    Route::post('/fillUp/{store}', "fillUp")->name('store.goods.fillUp'); //上架商品
                    Route::post('/restore/{store}', "restore")->name('store.goods.restore'); //上架商品
                    Route::get('/refreshCache/{store}', "refreshCache")->name('store.goods.refreshCache'); //属性商品缓存
                    Route::get('/newIndex/{store}', "newIndex")->name('store.goods.newIndex'); //属性商品缓存
                });
            });
            Route::prefix('account')->group(function () {
                Route::controller(AccountController::class)->group(function () {
                    Route::get('/log', "log")->name('store.account.log');
                    Route::post('/change/{storeId}', "change")->name('store.account.change');
                    Route::post('/withdrawal/{storeId}', "withdrawal")->name('store.account.withdrawal');
                    Route::post('/commission/{storeId}', "commission")->name('store.account.commission');
                });
            });
            Route::apiResource("account", AccountController::class, ['names' => 'store.account']);
        });

        Route::controller(SpecController::class)->group(function () {
            Route::prefix('spec')->group(function () {
                Route::post('/switch/{spec}', "switch")->name('spec.switch');
            });
        });

        Route::controller(AttrController::class)->group(function () {
            Route::prefix('attr')->group(function () {
                Route::post('/switch/{type}/{attr}', "switch")->name('attr.switch');
            });
        });
        Route::controller(AttrController::class)->group(function () {
            Route::prefix('attr')->group(function () {
                Route::post('/switch/{type}/{attr}', "switch")->name('attr.switch');
            });
        });

        Route::controller(GoodsController::class)->group(function () {
            Route::prefix('goods')->group(function () {
                Route::get('/sku', "sku")->name('goods.sku');
                Route::get('/count', "count")->name('goods.count');
                Route::post('/state/{goods}', "state")->name('goods.state');
                Route::post('/restore/{goods}', "restore")->name('goods.restore');
                Route::delete('/forceDelete/{goods}', "forceDelete")->name('goods.forceDelete');
                Route::post('/batch/{goods}', "batch")->name('goods.batch'); //批量操作
            });
        });

        Route::controller(MaterialController::class)->group(function () {
            Route::prefix('material')->group(function () {
                Route::post('state/{id}', "state")->name('material.state'); //状态切换
            });
        });


        Route::prefix('recipe')->group(function () {
            Route::controller(RecipeController::class)->group(function () {
                Route::post('/copy/{id}', "copy")->name('recipe.copy'); //状态切换
            });
            Route::prefix('goods')->group(function () {
                Route::controller(RecipeGoodsController::class)->group(function () {
                    Route::get('category/{recipe}/{type}', "category")->name('recipeGoods.category'); //状态切换
                    Route::get('/{recipe}/{type}', "index")->name('recipeGoods.index'); //状态切换
                    Route::post('/{recipe}/{type}', "store")->name('recipeGoods.store'); //状态切换
                    Route::put('/{recipe}/{type}', "update")->name('recipeGoods.update'); //状态切换
                    Route::delete('/{recipe}/{type}', "destroy")->name('recipeGoods.destroy'); //状态切换
                });
            });
            Route::prefix('store')->group(function () {
                Route::controller(RecipeStoreController::class)->group(function () {
                    Route::get('/{recipe}', "index")->name('recipeStore.index'); //状态切换
                    Route::post('/{recipe}', "store")->name('recipeStore.store'); //状态切换
                    Route::delete('/{recipe}', "destroy")->name('recipeStore.destroy'); //状态切换
                });
            });
        });

        Route::prefix('goodsRecommend')->group(function () {
            Route::controller(RecommendController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('goodsRecommend.state'); //状态切换
            });
            Route::prefix('goods')->group(function () {
                Route::controller(\App\Http\Controllers\Channel\GoodsRecommend\GoodsController::class)->group(function () {
                    Route::get('/{goodsRecommend}/{type}', "index")->name('goods.goodsRecommend.index'); //状态切换
                    Route::post('/{goodsRecommend}/{type}', "store")->name('goods.goodsRecommend.store'); //状态切换
                    Route::put('/{goodsRecommend}/{type}', "update")->name('goods.goodsRecommend.update'); //状态切换
                    Route::delete('/{goodsRecommend}/{type}', "destroy")->name('goods.goodsRecommend.destroy'); //状态切换
                });
            });
            Route::prefix('store')->group(function () {
                Route::controller(GoodsRecommendStoreController::class)->group(function () {
                    Route::get('/{type}', "index")->name('recommendStore.index'); //状态切换
                    Route::post('/{type}', "store")->name('recommendStore.store'); //状态切换
                    Route::delete('/{type}', "destroy")->name('recommendStore.destroy'); //状态切换
                });
            });
        });
        Route::prefix('member')->group(function () {
            Route::controller(MemberController::class)->group(function () {
                Route::post('state/{member}', 'state')->name('member.state');
                Route::post('changeBalance/{member}', 'changeBalance')->name('member.changeBalance');
                Route::post('changeIntegral/{member}', 'changeIntegral')->name('member.changeIntegral');
                Route::post('changeExp/{member}', 'changeExp')->name('member.changeExp');
                Route::post('changeVip/{member}', 'changeVip')->name('member.changeVip');
                Route::post('changeGroup/{member}', 'group')->name('member.changeGroup');
                Route::post('changeLabel/{member}', 'label')->name('member.changeLabel');
                Route::post('importMembers', 'importMembers')->name('member.importMembers');
                Route::get('memberExport', 'memberExport')->name('member.memberExport');
                Route::post('batchForm', 'batchForm')->name('member.batchForm');
                Route::get('filterUser', 'filterUser')->name('member.filterUser');
            });
            Route::prefix('accountLog')->group(function () {
                Route::controller(AccountLogController::class)->group(function () {
                    Route::get('/all/{type}', "all")->name('accountLog.all'); //状态切换
                });
            });
            Route::prefix('vip')->group(function () {
                Route::controller(VipController::class)->group(function () {
                    Route::post('/state/{id}', "state")->name('vip.state'); //状态切换
                });
            });
            Route::apiResource("job", JobController::class, ['names' => 'member.job']);
            Route::apiResource("accountLog", AccountLogController::class, ['names' => 'member.accountLog']);
            Route::apiResource("label", LabelController::class, ['names' => 'member.label']);
            Route::apiResource("group", GroupController::class, ['names' => 'member.group']);
            Route::apiResource("vip", VipController::class, ['names' => 'member.vip']);
            Route::apiResource("vipPower", VipPowerController::class, ['names' => 'member.vipPower']);
        });

        Route::prefix('drag')->group(function () {
            Route::controller(DragController::class)->group(function () {
                Route::post('release/{drag}', "release")->name('drag.release'); //状态切换
                Route::get('getRelease/{drag}', "getRelease")->name('drag.getRelease'); //状态切换
                Route::get('tempList', "tempList")->name('drag.tempList'); //状态切换
                Route::post('saveTemp/{drag}', "saveTemp")->name('drag.saveTemp'); //状态切换
            });
        });

        Route::prefix('prints')->group(function () {
            Route::controller(HardwareController::class)->group(function () {
                Route::get('store', "storeList")->name('prints.storeList'); //状态切换
            });
            Route::prefix('rule')->group(function () {
                Route::controller(PrintsRuleController::class)->group(function () {
                    Route::post('/{printId}', "store")->name('prints.rule.store'); //状态切换
                    Route::put('/{printId}', "update")->name('prints.rule.update'); //状态切换
                });
            });
            Route::apiResources([
                'hardware' => HardwareController::class,
                'rule' => PrintsRuleController::class
            ]);
        });

        Route::prefix('delivery')->group(function () {
            Route::controller(RuleController::class)->group(function () {
                Route::prefix('rule')->group(function () {
                    Route::post('saveStore/{id}', "saveStore")->name('delivery.rule.saveStore'); //状态切换
                });
            });

            Route::prefix('maiyatian')->group(function () {
                Route::controller(MaiyatianController::class)->group(function () {
                    Route::get('h5/{id}', "h5")->name('delivery.maiyatian.h5'); //状态切换
                    Route::get('citys', "citys")->name('delivery.maiyatian.citys'); //状态切换
                    Route::get('district', "district")->name('delivery.maiyatian.district'); //状态切换
                    Route::get('category', "category")->name('delivery.maiyatian.category'); //状态切换
                    Route::post('', "store")->name('delivery.maiyatian.store'); //状态切换
                });
            });

            Route::prefix('order')->group(function () {
                Route::controller(DeliveryOrderController::class)->group(function () {
                });
            });
            Route::apiResource("rule", RuleController::class, ['names' => 'delivery.rule']);
            Route::apiResource("store", DeliveryStoreController::class, ['names' => 'delivery.store']);
            Route::apiResource("channel", ChannelController::class, ['names' => 'delivery.channel']);
            Route::apiResource("order", DeliveryOrderController::class, ['names' => 'delivery.order']);
        });
        Route::prefix('order')->group(function () {
            Route::controller(OrderController::class)->group(function () {
                Route::get('count', "count")->name('order.count'); //状态切换
                Route::post('received/{id}', "received")->name('order.received'); //接单
                Route::post('maked/{id}', "maked")->name('order.maked'); //制作完成
                Route::post('delivery/{id}', "delivery")->name('order.delivery'); //配送
                Route::post('complete/{id}', "complete")->name('order.complete'); //完成
                Route::post('refund/{id}', "refund")->name('order.refund'); //退款
                Route::post('reject/{id}', "reject")->name('order.reject'); //申请退款
                Route::post('printOrder/{id}', "printOrder")->name('order.printOrder'); //申请退款
                Route::post('notes/{id}', "notes")->name('order.notes');
                Route::post('close/{id}', "close")->name('order.close');
                Route::post('printTest/{id}', "printTest")->name('order.printTest');
                Route::get('orderDataExport', "orderDataExport")->name('order.orderDataExport');
                Route::post('cancelPrint', "cancelPrint")->name('order.cancelPrint');
                Route::post('changePrint', "changePrint")->name('order.changePrint');
                Route::post('prepare', "prepare")->name('order.prepare');
                Route::post('verify', "verify")->name('order.verify');
                Route::post('revokeVerify', "revokeVerify")->name('order.revokeVerify');
                Route::post('getStoreList', "getStoreList")->name('order.getStoreList');
                Route::post('getTiktokVerifyList', "getTiktokVerifyList")->name('order.getTiktokVerifyList');
                Route::get('tiktokOrderDataExport', "tiktokOrderDataExport")->name('order.tiktokOrderDataExport');

                Route::get('financialDataExport', "financialDataExport")->name('order.financialDataExport');
                Route::get('storedValueConsumptionExport', "storedValueConsumptionExport")->name('order.storedValueConsumptionExport');
                Route::get('storeAccountExport', "storeAccountExport")->name('order.storeAccountExport');
                Route::get('flowRecordExport', "flowRecordExport")->name('order.flowRecordExport');
                Route::get('ledgerDetailsExport', "ledgerDetailsExport")->name('order.ledgerDetailsExport');
                Route::post('wxDelivery', "wxDelivery")->name('order.wxDelivery');
                Route::post('/selfVerify/{id}', "selfVerify")->name('order.selfVerify');
            });
        });
        Route::prefix('statistics')->group(function () {
            Route::controller(StatisticsController::class)->group(function () {
                Route::get('/', "index")->name('statistics.index'); //状态切换
                Route::get('/member', "member")->name('statistics.member'); //状态切换
                Route::get('/order', "order")->name('statistics.member'); //状态切换
                Route::get('/newOrder', "newOrder")->name('statistics.newOrder'); //状态切换
                Route::get('/memberStatistics', "memberStatistics")->name('statistics.memberStatistics'); //状态切换
                Route::get('/storedValue', "storedValue")->name('statistics.storedValue'); //状态切换
                Route::get('/goods', "goods")->name('statistics.goods'); //状态切换
                Route::get('/goodsCat', "goodsCat")->name('statistics.goodsCat'); //状态切换
                Route::get('/storedValueView/{id}', "storedValueView")->name('statistics.storedValueView'); //状态切换
                Route::get('/storeValuestatisticsExport', "storeValuestatisticsExport")->name('statistics.storeValuestatisticsExport'); //状态切换
                Route::get('/orderExport', "orderExport")->name('statistics.orderExport'); //状
                Route::get('/getToker', "getToker")->name('statistics.getToker');
            });
        });

        Route::prefix('messageConfig')->group(function () {
            Route::controller(MessageConfigController::class)->group(function () {
                Route::get('/{channelType}', "index")->name('messageConfig.index'); //状态切换
                Route::get('/view/{id}', "view")->name('messageConfig.view'); //状态切换
                Route::put('/{type}', "update")->name('messageConfig.update'); //状态切换
                Route::post('/subMessage/{type}', "subMessage")->name('messageConfig.subMessage'); //状态切换
            });
        });
        Route::prefix('import')->group(function () {
            Route::controller(ImportController::class)->group(function () {
                Route::post('/{type}', "store")->name('import.store'); //状态切换
            });
        });
        Route::prefix('roles')->group(function () {
            Route::controller(RoleController::class)->group(function () {
                Route::get('/list', "list")->name('roles.list'); //状态切换
            });
        });

        Route::prefix('waisongbang')->group(function () {
            Route::controller(WaiSongBangController::class)->group(function () {
                Route::post('', "store")->name('waisongbang.store'); //开通配送
                Route::post('/deliverShop/{shipWay}', "deliverShop")->name('waisongbang.deliverShop'); //开通配送
                Route::get('/deliverShopQuery/{shipWay}', "deliverShopQuery")->name('waisongbang.deliverShopQuery'); //查询配送状态
                Route::get('/balance', "balance")->name('waisongbang.deliverShop'); //查询余额
                Route::post('/charge', "charge")->name('waisongbang.charge'); //充值
                Route::get('/chargeState/{chargeId}', "chargeState")->name('waisongbang.chargeState'); //查询充值状态
            });
        });
        Route::prefix('qld')->group(function () {
            Route::controller(QldController::class)->group(function () {
                Route::post('', "store")->name('qld.store');
            });
        });
        Route::prefix('discount')->group(function () {
            Route::prefix('newSub')->group(function () {
                Route::controller(NewSubController::class)->group(function () {
                    Route::post('/state/{id}', "state")->name('discount.newSub.state');
                    Route::get('/receive', "receive")->name('discount.newSub.receive');
                    Route::get('/orderDataExport', "orderDataExport")->name('discount.newSub.orderDataExport');
                });
            });
            Route::prefix('fullsub')->group(function () {
                Route::controller(FullSubController::class)->group(function () {
                    Route::post('/state/{id}', "state")->name('discount.fullsub.state'); //查询充值状态
                    Route::get('/receive', "receive")->name('discount.fullsub.receive');
                });
            });
            Route::apiResource("fullsub", FullSubController::class, ['names' => 'discount.fullsub']);
            Route::apiResource("newSub", NewSubController::class, ['names' => 'discount.newSub']);
        });
        Route::prefix('coupon')->group(function () {
            Route::controller(CouponController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('coupon.state'); //查询充值状态
            });
            Route::prefix('receive')->group(function () {
                Route::controller(CouponReceiveController::class)->group(function () {
                    Route::post('/verification/{id}', "verification")->name('coupon.receive.verification'); //查询充值状态
                });
            });
            Route::apiResource("receive", CouponReceiveController::class, ['names' => 'coupon.receive']);
        });
        Route::apiResource("coupon", CouponController::class, ['names' => 'coupon.receive']);
        Route::prefix('helpers')->group(function () {
            Route::controller(HelpersController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('helpers.state'); //查询充值状态
                Route::get('/shop', "shop")->name('helpers.shop'); //查询充值状态
            });
        });
        Route::prefix('windowCoupon')->group(function () {
            Route::controller(WindowCouponController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('windowCoupon.state'); //查询充值状态
                Route::get('/receive', "receive")->name('windowCoupon.receive');
                Route::get('/orderDataExport', "orderDataExport")->name('windowCoupon.orderDataExport');
            });
        });
        Route::prefix('orderCollect')->group(function () {
            Route::controller(OrderCollectController::class)->group(function () {
                Route::get('/receive', "receive")->name('orderCollect.receive');
                Route::get('/orderDataExport', "orderDataExport")->name('orderCollect.orderDataExport');
            });
        });
        Route::prefix('giftBig')->group(function () {
            Route::controller(GiftBigController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('giftBig.state'); //查询充值状态
                Route::get('/receive', "receive")->name('giftBig.receive'); //查询充值状态
                Route::get('/orderDataExport', "orderDataExport")->name('giftBig.orderDataExport');
            });
        });

        Route::prefix('payGift')->group(function () {
            Route::controller(PayGiftController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('payGift.state'); //查询充值状态
                Route::get('/receive', "receive")->name('payGift.receive');
                Route::get('/orderDataExport', "orderDataExport")->name('payGift.orderDataExport');
            });
        });

        Route::prefix('exchangeCode')->group(function () {
            Route::controller(ExchangeCodeController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('exchangeCode.state');
                Route::get('/receive', "receive")->name('exchangeCode.receive');
                Route::get('/qrCode/{id}', "qrCode")->name('exchangeCode.qrCode');
                Route::delete('/receiveDelete/{id}', "receiveDelete")->name('exchangeCode.receiveDelete');
                Route::get('/batchExchangeCode', "batchExchangeCode")->name('exchangeCode.batchExchangeCode');

            });
        });

        Route::prefix('finance')->group(function () {
            Route::apiResource("balance", BalanceController::class, ['names' => 'finance.balance']);
            Route::apiResource("olineOrder", OlineOrderController::class, ['names' => 'finance.olineOrder']);
        });
        Route::prefix('table')->group(function () {
            Route::controller(TableController::class)->group(function () {
                Route::get('/qrcode/{id}', "qrcode")->name('table.qrcode');
                Route::post('/batch', "batch")->name('table.batch');
                Route::post('/clear/{id}', "clear")->name('table.clear');
                Route::get('/batchDownload', "batchDownload")->name('table.batchDownload');
            });
            Route::apiResource("table", TableController::class, ['names' => 'table.table']);
            Route::apiResource("area", AreaController::class, ['names' => 'table.area']);
            Route::apiResource("type", TypeController::class, ['names' => 'table.type']);
        });
        Route::prefix('inStore')->group(function () {
            Route::prefix('order')->group(function () {
                Route::controller(InStoreOrderController::class)->group(function () {
                    Route::post('received/{id}', "received")->name('inStore.order.received'); //接单
                    Route::post('complete/{id}', "complete")->name('inStore.order.complete'); //完成
                    Route::post('maked/{id}', "maked")->name('inStore.order.maked'); //完成
                    Route::any('callNum/{id}', "callNum")->name('inStore.order.callNum'); //完成
                    Route::post('close/{id}', "close")->name('inStore.order.close'); //完成
                    Route::post('refund/{id}', "refund")->name('inStore.order.refund'); //完成
                    Route::post('pay/{id}', "pay")->name('inStore.order.pay'); //完成
                    Route::post('notes/{id}', "notes")->name('inStore.order.notes'); //完成
                    Route::post('give/{id}', "give")->name('inStore.order.give'); //完成
                    Route::post('print/{id}', "print")->name('inStore.order.print'); //打印
                    Route::get('count', "count")->name('inStore.order.count'); //打印
                    Route::post('/discount/{id}', "discount")->name('inStore.order.discount');
                    Route::post('/wipeZero/{id}', "wipeZero")->name('inStore.order.wipeZero');
                    Route::post('/cancelDiscount/{id}', "cancelDiscount")->name('inStore.order.cancelDiscount');
                    Route::post('/free/{id}', "free")->name('inStore.order.free');
                    Route::get('/orderDataExport', "orderDataExport")->name('inStore.order.orderDataExport');
                });
            });
            Route::prefix('cart')->group(function () {
                Route::controller(InStoreCartController::class)->group(function () {
                    Route::post('/checkout', "checkout")->name('inStore.cart.checkout');
                    Route::post('/give', "give")->name('inStore.cart.give');
                    Route::delete('/clear', "clear")->name('inStore.cart.clear');
                    Route::post('/pack', "pack")->name('inStore.cart.pack');
                    Route::post('/packAll', "packAll")->name('inStore.cart.packAll');
                    Route::post('/notes', "notes")->name('inStore.cart.notes');
                    Route::post('/freeze', "freeze")->name('inStore.cart.freeze');
                    Route::post('/price', "price")->name('inStore.cart.price');
                    Route::post('/temp', "temp")->name('inStore.cart.temp');
                });
            });
            Route::prefix('table')->group(function () {
                Route::controller(InStoreTableController::class)->group(function () {
                    Route::get('/count', "count")->name('inStore.table.count');
                    Route::post('/changePeople/{id}', "changePeople")->name('inStore.table.changePeople');
                    Route::post('/backTable/{id}', "backTable")->name('inStore.table.backTable');
                    Route::post('/changeTable/{id}', "changeTable")->name('inStore.table.changeTable');
                    Route::post('/combine/{id}', "combine")->name('inStore.table.combine');
                });
            });
            Route::prefix('goods')->group(function () {
                Route::controller(InStoreGoodsController::class)->group(function () {
                    Route::get('/search', "search")->name('inStore.goods.search');
                    Route::get('/category', "category")->name('inStore.goods.category');
                    Route::get('/goods', "goods")->name('inStore.goods.goods');
                });
            });
            Route::prefix('freezeOrder')->group(function () {
                Route::controller(FreezeOrderController::class)->group(function () {
                    Route::post('/unFreeze/{id}', "unFreeze")->name('inStore.freezeOrder.unFreeze');
                });
            });

            Route::prefix('checkout')->group(function () {
                Route::controller(CheckoutController::class)->group(function () {
                    Route::post('/notes', "notes")->name('inStore.checkout.notes');
                    Route::post('/discount', "discount")->name('inStore.checkout.discount');
                    Route::post('/wipeZero', "wipeZero")->name('inStore.checkout.wipeZero');
                    Route::post('/cancelDiscount', "cancelDiscount")->name('inStore.checkout.cancelDiscount');
                    Route::post('/free', "free")->name('inStore.checkout.free');
                    Route::post('/coupon', "coupon")->name('inStore.checkout.coupon');
                    Route::get('/costomPay', 'costomPay')->name('inStore.checkout.costomPay');
                });
            });
            Route::apiResource("checkout", CheckoutController::class, ['names' => 'inStore.checkout']);
            Route::apiResource("server", ServerController::class, ['names' => 'inStore.server']);
            Route::apiResource("order", InStoreOrderController::class, ['names' => 'inStore.order']);
            Route::apiResource("table", InStoreTableController::class, ['names' => 'inStore.table']);
            Route::apiResource("cart", InStoreCartController::class, ['names' => 'inStore.cart']);
            Route::apiResource("goods", InStoreGoodsController::class, ['names' => 'inStore.goods']);
            Route::apiResource("freezeOrder", FreezeOrderController::class, ['names' => 'inStore.freezeOrder']);
        });
        Route::prefix('takeScreen')->group(function () {
            Route::controller(TakeScreenController::class)->group(function () {
                Route::post('complete/{id}', "complete")->name('takeScreen.complete'); //接单
                Route::post('call/{id}', "call")->name('takeScreen.call'); //完成
                Route::post('maked/{id}', "maked")->name('takeScreen.maked'); //完成
                Route::get('count', "count")->name('takeScreen.count'); //完成
            });
        });

        Route::prefix('withdrawal')->group(function () {
            Route::controller(WithdrawalController::class)->group(function () {
                Route::post('cancel/{id}', "cancel")->name('withdrawal.cancel'); //接单
                Route::post('online/{id}', "online")->name('takeScreen.online'); //完成
                Route::post('offline/{id}', "offline")->name('takeScreen.offline'); //完成
                Route::post('reject/{id}', "reject")->name('takeScreen.reject'); //完成
            });
        });

        Route::prefix('personPay')->group(function () {
            Route::controller(PersonPayOrderController::class)->group(function () {
                Route::post('refund/{id}', "refund")->name('personPay.refund'); //接单
                Route::get('orderDataExport', "orderDataExport")->name('personPay.orderDataExport');
            });
        });
        Route::prefix('printTemplate')->group(function () {
            Route::controller(PrintTemplateController::class)->group(function () {
                Route::post('restoreDefault/{id}', "restoreDefault")->name('printTemplate.restoreDefault'); //接单
            });
        });

        Route::prefix('payConfig')->group(function () {
            Route::controller(PayConfigController::class)->group(function () {
                Route::post('wxReceivers/{id}', "wxReceiversAdd")->name('payConfig.wxReceiversAdd'); //接单
                Route::delete('wxReceivers/{id}', "wxReceiversDel")->name('payConfig.wxReceiversDel'); //接单
                Route::post('setMnoArray/{id}', "setMnoArray")->name('payConfig.setMnoArray'); //接单
                Route::post('getFileUrl', "getFileUrl")->name('payConfig.getFileUrl');
            });
        });
        Route::prefix('profit')->group(function () {
            Route::controller(ProfitController::class)->group(function () {
                Route::post('profitsharing/{id}', "profitsharing")->name('payConfig.wxReceiversAdd'); //接单
                Route::post('unfreeze/{id}', "unfreeze")->name('payConfig.wxReceiversDel'); //接单
            });
        });
        Route::prefix('storedValueOrder')->group(function () {
            Route::controller(StoredValueOrderController::class)->group(function () {
                Route::get('list', "list")->name('storedValueOrder.list'); //接单
                Route::get('orderDataExport', "orderDataExport")->name('storedValueOrder.orderDataExport'); //接单
            });
        });
        Route::prefix('storedValue')->group(function () {
            Route::controller(StoredValueController::class)->group(function () {
                Route::post('subMessage/{id}', "subMessage")->name('storedValue.subMessage'); //接单
            });
        });
        Route::prefix('delivery')->group(function () {
            Route::controller(ChannelController::class)->group(function () {
                Route::get('/supplierQuery', "supplierQuery")->name('delivery.supplierQuery'); //开通配送
                Route::post('/storeValue', "storeValue")->name('delivery.storeValue');
                Route::post('/createreally', "createreally")->name('delivery.createreally');
                Route::post('/storeAuthorization', "storeAuthorization")->name('delivery.storeAuthorization');
            });
        });

        Route::prefix('handover')->group(function () {
            Route::controller(HandoverController::class)->group(function () {
                Route::get('/starting', "starting")->name('handover.starting'); //开通配送
            });
        });

        Route::prefix('couponActivity')->group(function () {
            Route::controller(CouponActivityController::class)->group(function () {
                Route::get('/qrCode/{id}', "qrCode")->name('couponActivity.qrCode'); //开通配送
            });
        });

        Route::prefix('pointsMallOrder')->group(function () {
            Route::controller(PointsMallOrderController::class)->group(function () {
                Route::post('/delivery/{id}', "delivery")->name('pointsMallOrder.delivery'); //开通配送
                Route::post('/refund/{id}', "refund")->name('pointsMallOrder.refund'); //开通配送
                Route::post('/verification/{id}', "verification")->name('pointsMallOrder.verification'); //开通配送
                Route::post('/rejectRefund/{id}', "rejectRefund")->name('pointsMallOrder.rejectRefund'); //开通配送
                Route::post('/notes/{id}', "notes")->name('pointsMallOrder.notes'); //开通配送
            });
        });

        Route::prefix('pointsMall')->group(function () {
            Route::controller(PointsMallController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('pointsMall.state'); //开通配送
            });
        });

        Route::prefix('vipGoods')->group(function () {
            Route::controller(VipGoodsController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('pointsMall.state'); //开通配送
            });
        });

        Route::prefix('goodsDiscount')->group(function () {
            Route::controller(GooodsDiscountController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('goodsDiscount.state'); //开通配送
            });
        });

        Route::prefix('queuingUp')->group(function () {
            Route::controller(QueuingUpController::class)->group(function () {
                Route::get('/url/{id}', "url")->name('queuingUp.url'); //开通配送
                Route::get('/clear', "clear")->name('queuingUp.clear'); //开通配送
                Route::get('/call/{id}', "call")->name('queuingUp.call'); //开通配送
                Route::get('/statistics', "statistics")->name('queuingUp.statistics'); //开通配送
                Route::get('/view/{id}', "view")->name('queuingUp.view'); //开通配送
            });
        });

        Route::prefix('couponPack')->group(function () {
            Route::controller(CouponPackController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('couponPack.state');
            });
        });

        Route::prefix('couponPackOrder')->group(function () {
            Route::controller(couponPackOrderController::class)->group(function () {
                Route::post('/refund/{id}', "refund")->name('couponPackOrder.refund');
            });
        });
        Route::prefix('tableReserveOrder')->group(function () {
            Route::controller(CouponPackController::class)->group(function () {
                Route::post('/refund/{id}', "refund")->name('couponPack.refund');
            });
        });

        Route::prefix('tableReserveOrder')->group(function () {
            Route::controller(TableReserveOrderController::class)->group(function () {
                Route::post('/refund/{id}', "refund")->name('tableReserveOrder.refund');
                Route::post('/received/{id}', "received")->name('tableReserveOrder.received');
            });
        });
        Route::prefix('oldWithNew')->group(function () {
            Route::controller(ActivityController::class)->group(function () {
                Route::get('/receive', "receive")->name('oldWithNew.receive');
                Route::get('/partyB', "partyB")->name('wordCoupon.partyB');
            });
            Route::apiResource("activity", ActivityController::class, ['names' => 'oldWithNew.activity']);
        });
        Route::prefix('wordCoupon')->group(function () {
            Route::controller(WordCouponController::class)->group(function () {
                Route::get('/receive', "receive")->name('wordCoupon.receive');
            });
        });
        Route::prefix('drinks')->group(function () {
            Route::controller(DrinksController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('drinks.state');
                Route::get('/statistics', 'statistics')->name('drinks.statistics');
                Route::post('/printDrinksOrder/{id}', "printDrinksOrder")->name('drinks.printDrinksOrder');
            });
            Route::controller(LogController::class)->group(function () {
                Route::post('/pass/{id}', "state")->name('drinks.log.pass');
                Route::post('/refuse/{id}', "state")->name('drinks.log.refuse');
            });
            Route::apiResource("drinks", DrinksController::class, ['names' => 'drinks.drinks']);
            Route::apiResource("order", DrinksOrderController::class, ['names' => 'drinks.order']);
            Route::apiResource("log", LogController::class, ['names' => 'drinks.log']);
            Route::apiResource("storage", StorageController::class, ['names' => 'drinks.storage']);
        });

        Route::controller(WechatController::class)->group(function () {
            Route::prefix('wechat')->group(function () {
                Route::post('materialAdd', "materialAdd")->name('wechat.materialAdd'); //获取公众号信息
                Route::get('materialGet', "materialGet")->name('wechat.materialGet'); //获取公众号信息
                Route::get('menus', "menus")->name('wechat.menus'); //获取公众号信息
                Route::post('menuCreate', "menuCreate")->name('wechat.menuCreate'); //获取公众号信息
                Route::get('h5', "h5")->name('wechat.h5'); //获取公众号信息
                Route::post('checkName', "checkName")->name('wechat.checkName');
            });
        });


        Route::controller(WechatReplyController::class)->group(function () {
            Route::prefix('wechatReply')->group(function () {
                Route::get('focus', "focus")->name('wechatReply.get'); //获取公众号信息
                Route::get('key', "key")->name('wechatReply.key'); //获取公众号信息
                Route::get('default', "default")->name('wechatReply.default'); //获取公众号信息
                Route::post('state/{wechatReply}', "state")->name('wechatReply.state'); //获取公众号信息
            });
        });

        Route::prefix('equityCard')->group(function () {
            Route::controller(EquityCardController::class)->group(function () {
                Route::get('/state/{id}', "state")->name('equityCard.state');
                Route::get('/order', "order")->name('equityCard.order');
            });
        });

        Route::prefix('partner')->group(function () {
            Route::controller(PartnerController::class)->group(function () {
                Route::post('/auth/{id}', "auth")->name('partner.auth');
                Route::post('/refuse/{id}', "refuse")->name('partner.refuse');
                Route::get('/order', "order")->name('partner.order');
            });
        });
        Route::prefix('store_partner')->group(function () {
            Route::controller(storePartnerController::class)->group(function () {
                Route::get('/', "index")->name('storePartner.index');
                Route::get('/order', "order")->name('storePartner.order');
            });
        });
        Route::controller(UserWithdrawalController::class)->group(function () {
            Route::prefix('userWithdrawal')->group(function () {
                Route::post('/online/{withdrawal}', "online")->name('userWithdrawal.online');
                Route::post('/offline/{withdrawal}', "offline")->name('userWithdrawal.offline');
                Route::post('/reject/{withdrawal}', "reject")->name('userWithdrawal.reject');
                Route::get('/WithdrawalExport', "WithdrawalExport")->name('userWithdrawal.WithdrawalExport');
            });
        });

        Route::prefix('tradeIn')->group(function () {
            Route::controller(TradeInGoodsController::class)->group(function () {
                Route::post('/state/{id}', "state")->name('couponPack.state');
            });
        });
        Route::prefix('luckyWheelRecord')->group(function () {
            Route::controller(LuckyWheelRecordController::class)->group(function () {
                Route::get('/records', "records")
                    ->name('luckyWheelRecord.records');
            });
        });
        Route::apiResources([
            'luckyWheelRecord' => LuckyWheelRecordController::class, //大转盘中奖记录
            'luckyWheel' => LuckyWheelController::class,
            'luckyWheelReward' => LuckyWheelRewardController::class
        ]);
        Route::apiResources([
            'admins' => AdminController::class, // 超级管理员
            "apply" => ApplyController::class, //平台
            "muster" => MusterController::class, //平台
            "mini-upload" => MiniUploadController::class, //平台
            "payTemplate" => PayTemplateController::class, //平台
            "payConfig" => PayConfigController::class, //平台
            'plug' => PlugController::class,
            'wechatReply' => WechatReplyController::class,
            'store' => StoreController::class,
            'storeLabel' => StoreLabelController::class,
            'storeGroup' => StoreGroupController::class,
            'goodsCat' => GoodsCatController::class,
            'goodsCatLabel' => GoodsCatLabelController::class,
            'goodsLabel' => GoodsLabelController::class,
            'goodsUnit' => GoodsUnitController::class,
            "spec" => SpecController::class,
            "attr" => AttrController::class,
            'goodsMark' => GoodsMarkController::class,
            'material' => MaterialController::class,
            'drag' => DragController::class,
            "goods" => GoodsController::class,
            "materialCat" => MaterialCatController::class,
            'recipe' => RecipeController::class,
            "storeConfig" => StoreConfigController::class,
            'member' => MemberController::class,
            'goodsRecommend' => RecommendController::class,
            'storedValue' => StoredValueController::class,
            'order' => OrderController::class,
            'printerLog' => PrinterLogController::class,
            'notice' => NoticeController::class,
            'handleLog' => HandleLogController::class,
            'ad' => AdController::class,
            'menus' => MenuController::class,
            "roles" => RoleController::class,
            'storedValueOrder' => StoredValueOrderController::class,
            'helpers' => HelpersController::class,
            'windowCoupon' => WindowCouponController::class,
            'giftBig' => GiftBigController::class,
            'payGift' => PayGiftController::class,
            'pointsMallClassification' => PointsMallClassificationController::class,
            'pointsMall' => PointsMallController::class,
            'orderCollect' => OrderCollectController::class,
            'exchangeCode' => ExchangeCodeController::class,
            'storePayConfig' => StorePayConfigController::class,
            'advertisement' => AdvertisementController::class,
            'specValue' => SpecValueController::class,
            'attrValue' => AttrValueController::class,
            'personPay' => PersonPayOrderController::class,
            'pointSign' => PointSignController::class,
            'signList' => SignListController::class,
            'takeScreen' => TakeScreenController::class,
            'printTemplate' => PrintTemplateController::class,
            'printStoreTemplate' => PrintStoreTemplateController::class,
            'couponActivity' => CouponActivityController::class,
            'withdrawal' => WithdrawalController::class,
            'profit' => ProfitController::class,
            'voiceMessage' => VoiceMessageController::class,
            'handover' => HandoverController::class,
            'pointsMallOrder' => PointsMallOrderController::class,
            'vipGoods' => VipGoodsController::class,
            'goodsDiscount' => GooodsDiscountController::class,
            'queuingUp' => QueuingUpController::class,
            'couponPack' => CouponPackController::class,
            'couponPackOrder' => CouponPackOrderController::class,
            'tableReserveOrder' => TableReserveOrderController::class,
            'costomPay' => CostomPayController::class,
            'wordCoupon' => WordCouponController::class,
            'equityCard' => EquityCardController::class,
            'partner' => PartnerController::class,
            'userWithdrawal' => UserWithdrawalController::class,
            'tradeIn' => TradeInGoodsController::class,
            'awaken' => AwakenController::class,
            'goodsLog' => GoodsLogController::class
        ]);
    });
});
