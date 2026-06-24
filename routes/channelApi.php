<?php

use App\Http\Controllers\ChannelApi\AdController;
use App\Http\Controllers\ChannelApi\AddressController;
use App\Http\Controllers\ChannelApi\CartController;
use App\Http\Controllers\ChannelApi\CollectController;
use App\Http\Controllers\ChannelApi\ConfigController;
use App\Http\Controllers\ChannelApi\CouponActivityController;
use App\Http\Controllers\ChannelApi\CouponController;
use App\Http\Controllers\ChannelApi\CouponPackController;
use App\Http\Controllers\ChannelApi\CouponPackOrderController;
use App\Http\Controllers\ChannelApi\CouponRegiftController;
use App\Http\Controllers\ChannelApi\DragController;
use App\Http\Controllers\ChannelApi\Drinks\LogController;
use App\Http\Controllers\ChannelApi\Drinks\OrderController as DrinksOrderController;
use App\Http\Controllers\ChannelApi\EquityCardController;
use App\Http\Controllers\ChannelApi\EquityCardOrderController;
use App\Http\Controllers\ChannelApi\ExchangeCodeController;
use App\Http\Controllers\ChannelApi\FileController;
use App\Http\Controllers\ChannelApi\GiftBigController;
use App\Http\Controllers\ChannelApi\GoodsController;
use App\Http\Controllers\ChannelApi\HelpersController;
use App\Http\Controllers\ChannelApi\InStore\CartController as InStoreCartController;
use App\Http\Controllers\ChannelApi\InStore\GoodsController as InStoreGoodsController;
use App\Http\Controllers\ChannelApi\InStore\OrderController as InStoreOrderController;
use App\Http\Controllers\ChannelApi\LoginController;
use App\Http\Controllers\ChannelApi\MessageConfigController;
use App\Http\Controllers\ChannelApi\OrderController;
use App\Http\Controllers\ChannelApi\PayController;
use App\Http\Controllers\ChannelApi\PersonPayController;
use App\Http\Controllers\ChannelApi\RegionController;
use App\Http\Controllers\ChannelApi\SmsController;
use App\Http\Controllers\ChannelApi\StoreController;
use App\Http\Controllers\ChannelApi\StoreValueController;
use App\Http\Controllers\ChannelApi\UserController;
use App\Http\Controllers\ChannelApi\WxPayNotifyController;
use App\Http\Controllers\ChannelApi\PointsMallClassificationController;
use App\Http\Controllers\ChannelApi\PointsMallController;
use App\Http\Controllers\ChannelApi\InStore\TableController;
use App\Http\Controllers\ChannelApi\VipCardController;
use App\Http\Controllers\ChannelApi\WindowCouponController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChannelApi\InStore\TableReserveController;
use App\Http\Controllers\ChannelApi\InStore\TableReserveOrderController;
use App\Http\Controllers\ChannelApi\OldWithNew\PartyAController;
use App\Http\Controllers\ChannelApi\OldWithNew\PartyBController;
use App\Http\Controllers\ChannelApi\PartnerController;
use App\Http\Controllers\ChannelApi\PointsMallOrderController;
use App\Http\Controllers\ChannelApi\QueuingUpController;
use App\Http\Controllers\ChannelApi\SignInController;
use App\Http\Controllers\ChannelApi\WithdrawalController;
use App\Http\Controllers\ChannelApi\WordCouponController;
use App\Http\Controllers\ChannelApi\publicMiniProgram\LoginController as PublicLogin;
use App\Http\Controllers\ChannelApi\publicMiniProgram\IndexController;
use App\Http\Controllers\Channel\LuckyWheelRewardController;
use App\Http\Controllers\ChannelApi\LuckyWheelController;
Route::get('/ws', function () {
    // 响应状态码200的任意内容
    return 'ok';
})->middleware(['jwt.channelApi']);

Route::controller(LoginController::class)->group(function () {
    Route::get('/wechat/authorizationUrl', "getAuthorizationUrl")->name('authorizationUrl');
    Route::post('/wechat/login', "Login")->name('login');
    Route::get('/wechat/{id}', "wechatLogin")->name('wechatLogin');
    Route::post('/wechat/register', "register")->name('register');
    Route::post('/mobileLogin', "mobileLogin")->name('mobileLogin');
    Route::post('/wechat/decrypt', "Decrypt")->name('decrypt');
    Route::get('/jssdk', "jssdk")->name('jssdk');
});

Route::controller(SmsController::class)->group(function () {
    Route::post('/sendSms', "sendSms")->name('sendSms');
});

//Route::prefix('delivery')->group(function () {
//    Route::controller(DeliveryController::class)->group(function () {
//        Route::any('/', "index")->name('delivery.index'); //状态切换
//    });
//});

Route::controller(ConfigController::class)->group(function () {
    Route::get('/config/{id}', "show")->name('config.show'); //状态切换
    Route::get('/storeConfig/{storeId}/{id}', "storeConfig")->name('config.storeConfig'); //状态切换
    Route::post('/configFormMap', "configFormMap")->name('config.configFormMap'); //状态切换
});


Route::prefix('region')->group(function () {
    Route::controller(RegionController::class)->group(function () {
        Route::get('/address', "address")->name('region.address'); //状态切换
        Route::get('', "index")->name('region.index'); //状态切换
    });
});
Route::prefix('store')->group(function () {
    Route::controller(StoreController::class)->group(function () {
        Route::get('/default', "default")->name('store.default'); //状态切换
    });
});
Route::prefix('goods')->group(function () {
    Route::controller(GoodsController::class)->group(function () {
        Route::get('/search/{id}', "search")->name('store.search'); //状态切换
        Route::get('/category', "category")->name('goods.category'); //状态切换
        Route::get('/goods', "goods")->name('goods.goods'); //状态切换
    });
});
Route::apiResources([
    'store' => StoreController::class,
    'goods' => GoodsController::class,
    'drag' => DragController::class,
]);

Route::prefix('store')->group(function () {
    Route::controller(StoreController::class)->group(function () {
        Route::get('/default', "default")->name('store.default'); //状态切换
    });
});

Route::controller(IndexController::class)->group(function (){
    // 获取公域门店
    Route::get('/getPublicStore', 'getPublicStore')->name('getPublicStore');
    Route::get('/getPublicBrand', 'getPublicBrand')->name('getPublicBrand');
    Route::get('/getStore', 'getStore')->name('getStore');
    Route::get('/getStoreGoods', 'getStoreGoods')->name('getStoreGoods');
    Route::get('/getCategory', 'getCategory')->name('getCategory');
    Route::get('/getSwiper', 'getSwiper')->name('getSwiper');
    Route::get('/getUrlScheme', 'getUrlScheme')->name('getUrlScheme');

});
Route::controller(PublicLogin::class)->group(function (){
    Route::post('/publicLogin', 'Login')->name('Login');
    Route::get('/getPublicConfig', 'appStore')->name('appStore');

});

Route::controller(WxPayNotifyController::class)->group(function () {
    Route::prefix('wxPayNotify')->group(function () {
        Route::post('/jsPay/{uniacid}/{payTemprateId}', "jsPay")->name('wxPayNotify.jsPay');
        Route::post('/fubei/{payTemprateId}', "fubei")->name('wxPayNotify.fubei');
        Route::post('/suixingfu/{payTemprateId}', "suixingfu")->name('wxPayNotify.suixingfu');
        Route::post('/aliPay/{uniacid}/{payTemprateId}', "aliPay")->name('wxPayNotify.jsPay');
        Route::post('/zhongyin/{uniacid}/{payTemprateId}', "zhongyin")->name('wxPayNotify.zhongyin');
        Route::post('/yidianfu/{payTemprateId}', "yidianfu")->name('wxPayNotify.yidianfu');
        Route::post('/laKaLaPay/{payTemprateId}', "LaKaLaPay")->name('wxPayNotify.LaKaLaPay');
        Route::post('/huiLaiMiPay/{payTemprateId}', "HuiLaiMiPay")->name('wxPayNotify.HuiLaiMiPay');

    });
});


Route::group(['middleware' => ['jwt.channelApi']], function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/profix', "index")->name('profix'); //获取个人信息;
        Route::post('/profix/edit', "update")->name('profix.update'); //更新个人信息;
        Route::post('/profix/mobile', "changeMobile")->name('profix.changeMobile'); //更新个人信息;
        Route::get('/accountLog', "accountLog")->name('accountLog'); //获取个人信息;
        Route::get('/integralLog', "integralLog")->name('integralLog'); //获取个人信息;
        Route::get('/my/coupon', "coupon")->name('coupon'); //获取个人信息;
        Route::get('/qrcode', "qrcode")->name('channelApi.user.qrcode');
        Route::get('/birthday', "birthday")->name('channelApi.user.birthday');
        Route::post('/logout', "logout")->name('logout');
        Route::post('/withdrawalConfig', "withdrawalConfig")->name('channelApi.user.withdrawalConfig'); //更新个人信息;
        Route::post('/withdrawal', "withdrawal")->name('channelApi.user.withdrawal'); //提现;
    });

    Route::controller(FileController::class)->group(function () {
        Route::prefix('file')->group(function () {
            Route::post('/upload', "upload")->name('file.upload');
            Route::post('/uploadBase64', "uploadBase64")->name('file.uploadBase64');
        });
    });

    Route::controller(CartController::class)->group(function () {
        Route::prefix('cart')->group(function () {
            Route::delete('/clear', "clear")->name('cart.clear');
            Route::post('/checkout', "checkout")->name('cart.checkout');
            Route::get('/address', "address")->name('cart.address');
            Route::post('/price', "price")->name('cart.price');
        });
    });

    Route::controller(OrderController::class)->group(function () {
        Route::prefix('order')->group(function () {
            Route::post('/refundApply/{id}', "refundApply")->name('order.refundApply');
            Route::post('/close/{id}', "close")->name('order.close');
            Route::post('/complete/{id}', "complete")->name('order.complete');
            Route::get('/backlog', "backlog")->name('order.backlog');
            Route::post('prepare', "prepare")->name('order.prepare');
            Route::post('verify', "verify")->name('order.verify');
            Route::post('revokeVerify', "revokeVerify")->name('order.revokeVerify');
            Route::get('getTiktokVerifyList', "getTiktokVerifyList")->name('order.getTiktokVerifyList');
        });
    });
    Route::controller(CollectController::class)->group(function () {
        Route::prefix('collect')->group(function () {
            Route::post('/{type}/{id}', "store")->name('channelApi.collect.store');
            Route::post('/{type}', "show")->name('channelApi.collect.show');
        });
    });
    Route::prefix('storeValue')->group(function () {
        Route::controller(StoreValueController::class)->group(function () {
            Route::get('order', "order")->name('channelApi.storeValue.order');
        });
    });

    Route::prefix('coupon')->group(function () {
        Route::controller(CouponController::class)->group(function () {
            Route::get('/qrcode/{id}', "qrcode")->name('channelApi.coupon.qrcode');
            Route::post('/verifyPrepare', "verifyPrepare")->name('channelApi.coupon.verifyPrepare');
            Route::post('/verifyCard', "verifyCard")->name('channelApi.coupon.verifyCard');
        });
    });

    Route::prefix('couponRegift')->group(function () {
        Route::controller(CouponRegiftController::class)->group(function () {
            Route::post('/receive/{id}', "receive")->name('channelApi.coupon.receive');
        });
    });

    Route::prefix('exchangeCode')->group(function () {
        Route::controller(ExchangeCodeController::class)->group(function () {
            Route::post('/coupon', "coupon")->name('channelApi.exchangeCode.coupon');
            Route::post('/balance', "balance")->name('channelApi.exchangeCode.balance');
        });
    });
    Route::prefix('inStore')->group(function () {
        Route::prefix('cart')->group(function () {
            Route::controller(InStoreCartController::class)->group(function () {
                Route::delete('/clear', "clear")->name('inStore.cart.clear');
                Route::post('/price', "price")->name('cart.price');
                Route::post('/checkout', "checkout")->name('cart.checkout');
                Route::post('/call', "checkout")->name('cart.checkout');
                Route::post('/packAll', "packAll")->name('cart.packAll');
            });
        });
        Route::prefix('table')->group(function () {
            Route::controller(TableController::class)->group(function () {
                Route::get('/callWaiter/{id}', "callWaiter")->name('inStore.table.callWaiter');
            });
        });
        Route::prefix('order')->group(function () {
            Route::controller(InStoreOrderController::class)->group(function () {
                Route::post('/close/{id}', "close")->name('inStore.order.close');
                // Route::post('/refundApply/{id}', "refundApply")->name('inStore.order.refundApply');
                Route::post('/complete/{id}', "complete")->name('inStore.order.refundApply');
                Route::get('/startOrder', "startOrder")->name('inStore.order.startOrder');
            });
        });
        Route::prefix('goods')->group(function () {
            Route::controller(InStoreGoodsController::class)->group(function () {
                Route::get('/search/{id}', "search")->name('inStore.goods.search');
                Route::get('/category', "category")->name('inStore.goods.search');
                Route::get('/goods', "goods")->name('inStore.goods.search');
            });
        });
        Route::apiResource("table", TableController::class, ['names' => 'channelApi.inStore.table']);
        Route::apiResource("cart", InStoreCartController::class, ['names' => 'channelApi.inStore.cart']);
        Route::apiResource("goods", InStoreGoodsController::class, ['names' => 'channelApi.inStore.goods']);
        Route::apiResource("order", InStoreOrderController::class, ['names' => 'channelApi.inStore.order']);
    });
    Route::prefix('personPay')->group(function () {
        Route::controller(PersonPayController::class)->group(function () {
            Route::get('checkout', "checkout")->name('channelApi.personPay.checkout');
        });
    });

    Route::prefix('pointsMall')->group(function () {
        Route::controller(PointsMallController::class)->group(function () {
            Route::get('checkout', "checkout")->name('channelApi.pointsMall.checkout');
        });
    });
    Route::prefix('pointsMallOrder')->group(function () {
        Route::controller(PointsMallOrderController::class)->group(function () {
            Route::post('refundApply/{orderSn}', "refundApply")->name('channelApi.pointsMallOrder.checkout');
            Route::post('close/{orderSn}', "close")->name('channelApi.pointsMallOrder.close');
            Route::post('complete/{orderSn}', "complete")->name('channelApi.pointsMallOrder.complete');
        });
    });

    Route::prefix('queuingUp')->group(function () {
        Route::controller(QueuingUpController::class)->group(function () {
            Route::get('table', "table")->name('channelApi.pointsMall.checkout');
        });
    });

    Route::prefix('tableReserve')->group(function () {
        Route::controller(TableReserveController::class)->group(function () {
            Route::get('/checkout', "checkout")->name('channelApi.tableReserve.checkout');
            Route::get('/tableType', "tableType")->name('channelApi.tableReserve.tableType');
        });
    });
    Route::prefix('oldWithNew')->group(function () {
        Route::controller(PartyAController::class)->group(function () {
            Route::get('/shear/{id}', "shear")->name('oldWithNew.partyA.shear');
            Route::get('/recordA/{id}', "recordA")->name('oldWithNew.partyA.recordA');
            Route::get('/recordB/{id}', "recordB")->name('oldWithNew.partyA.recordB');
        });
        Route::apiResource("partyA", PartyAController::class, ['names' => 'oldWithNew.partyA']);
        Route::apiResource("partyB", PartyBController::class, ['names' => 'oldWithNew.partyA']);
    });

    Route::prefix('pay')->group(function () {
        Route::controller(PayController::class)->group(function () {
            Route::get('/query/{orderSn}', "query")->name('channelApi.pay.query');
        });
    });

    Route::prefix('drinks')->group(function () {
        Route::apiResource("order", DrinksOrderController::class, ['names' => 'drinks.order']);
        Route::apiResource("log", LogController::class, ['names' => 'drinks.log']);
    });
    Route::prefix('equityCardOrder')->group(function () {
        Route::controller(EquityCardOrderController::class)->group(function () {
            Route::get('/myCard', "myCard")->name('equityCardOrder.myCard');
        });
    });

    Route::prefix('partner')->group(function () {
        Route::controller(PartnerController::class)->group(function () {
            Route::get('/share', "share")->name('partner.share');
            Route::get('/order', "order")->name('partner.order');
            Route::get('/downline', "downline")->name('partner.downline');
        });
    });
    Route::prefix('luckyWheel')->group(function () {
        Route::controller(LuckyWheelController::class)->group(function () {
            Route::get('/awardsForUser', "awardsForUser")->name('luckyWheel.awardsForUser');  //列表
            Route::get('/drawALottery', "drawALottery")->name('luckyWheel.drawALottery');  //抽奖
        });
    });
    Route::apiResources([
        'luckyWheel' => LuckyWheelController::class,
        'order' => OrderController::class, // 订单\
        'cart' => CartController::class, // 超级管理员\
        'address' => AddressController::class, // 超级管理员\
        "pay" => PayController::class,
        'messageConfig' => MessageConfigController::class,
        'ad' => AdController::class,
        'storeValue' => StoreValueController::class,
        'coupon' => CouponController::class,
        'helpers' => HelpersController::class,
        'couponRegift' => CouponRegiftController::class,
        'giftBig' => GiftBigController::class,
        'pointsMallClassification' => PointsMallClassificationController::class,
        'pointsMall' => PointsMallController::class,
        'windowCoupon' => WindowCouponController::class,
        'vipCard' => VipCardController::class,
        'personPay' => PersonPayController::class,
        'couponActivity' => CouponActivityController::class,
        'signIn' => SignInController::class,
        'pointsMallOrder' => PointsMallOrderController::class,
        'queuingUp' => QueuingUpController::class,
        'couponPack' => CouponPackController::class,
        'couponPackOrder' => CouponPackOrderController::class,
        'tableReserve' => TableReserveController::class,
        'tableReserveOrder' => TableReserveOrderController::class,
        'wordCoupon' => WordCouponController::class,
        'equityCard' => EquityCardController::class,
        'equityCardOrder' => EquityCardOrderController::class,
        'partner' => PartnerController::class,
        'withdrawal' => WithdrawalController::class
    ]);
});
