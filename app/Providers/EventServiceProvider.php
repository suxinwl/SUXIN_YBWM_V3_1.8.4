<?php

namespace App\Providers;

use App\Events\BirthdayGiftEvent;
use App\Events\CouponEvent;
use App\Events\EquityCardEvent;
use App\Events\MemberAccountEvent;
use App\Events\MemberGiftBigEvent;
use App\Events\OrderCollectEvent;
use App\Events\OrderMessageEvent;
use App\Events\PartnerEvent;
use App\Events\PartyBEvent;
use App\Events\PayGiftEvent;
use App\Events\SignInEvent;
use App\Events\StoreMessageEvent;
use App\Events\WordCouponEvent;
use App\Listeners\BirthdayPack\BalanceGiveListener as BirthdayPackBalanceGiveListener;
use App\Listeners\BirthdayPack\CouponGiveListener as BirthdayPackCouponGiveListener;
use App\Listeners\BirthdayPack\IntegralGiveListener as BirthdayPackIntegralGiveListener;
use App\Listeners\Coupon\MiniMessageListener as CouponMiniMessageListener;
use App\Listeners\EquityCard\CouponGiveListener as EquityCardCouponGiveListener;
use App\Listeners\GiftBig\BalanceGiveListener;
use App\Listeners\GiftBig\CouponGiveListener as GiftBigCouponGiveListener;
use App\Listeners\GiftBig\IntegralGiveListener;
use App\Listeners\Member\AccountListener;
use App\Listeners\Member\MiniMessageListener as MemberMiniMessageListener;
use App\Listeners\Member\SmsMessageListener as MemberSmsMessageListener;
use App\Listeners\Member\WechatMessageListener as MemberWechatMessageListener;

use App\Listeners\OrderCollect\BalanceGiveListener as OrderCollectBalanceGiveListener;
use App\Listeners\OrderCollect\CouponGiveListener as OrderCollectCouponGiveListener;
use App\Listeners\OrderCollect\IntegralGiveListener as OrderCollectIntegralGiveListener;
use App\Listeners\Partner\BillListener;
use App\Listeners\Partner\PayListener;
use App\Listeners\PartyB\CouponGiveListener as PartyBCouponGiveListener;
use App\Listeners\PartyB\IntegralGiveListener as PartyBIntegralGiveListener;
use App\Listeners\PayGift\BalanceGiveListener as PayGiftBalanceGiveListener;
use App\Listeners\PayGift\CouponGiveListener as PayGiftCouponGiveListener;
use App\Listeners\PayGift\IntegralGiveListener as PayGiftIntegralGiveListener;
use App\Listeners\SignIn\BalanceGiveListener as SignInBalanceGiveListener;
use App\Listeners\SignIn\CouponGiveListener as SignInCouponGiveListener;
use App\Listeners\SignIn\IntegralGiveListener as SignInIntegralGiveListener;
use App\Listeners\Store\MiniMessageListener as StoreMiniMessageListener;
use App\Listeners\Store\SmsMessageListener as StoreSmsMessageListener;
use App\Listeners\Store\SocketMessageListener;
use App\Listeners\Store\VoiceMessageListener;
use App\Listeners\Store\WechatMessageListener as StoreWechatMessageListener;
use App\Listeners\Store\WorkMessageListener;
use App\Listeners\VipGive\CouponGiveListener;
use App\Listeners\VipGive\MessageListener;
use App\Listeners\WordCoupon\BalanceGiveListener as WordCouponBalanceGiveListener;
use App\Listeners\WordCoupon\CouponGiveListener as WordCouponCouponGiveListener;
use App\Listeners\WordCoupon\IntegralGiveListener as WordCouponIntegralGiveListener;
use App\Models\Admin\Apply;
use App\Models\GoodsSpu;
use App\Observers\GoodsSpuObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Listeners\Partner\RefundListener;

use App\Listeners\Message\MiniMessageListener;
use App\Listeners\Message\SmsMessageListener;
use App\Listeners\Message\WechatMessageListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        'App\Events\MemberRegisteredEvent' => [
            'App\Listeners\VipGive\IntegralGiveListener', // vip赠送积分
            'App\Listeners\VipGive\BalanceGiveListener', // vip赠送余额
            CouponGiveListener::class
        ],
        OrderMessageEvent::class => [
            MiniMessageListener::class,
            WechatMessageListener::class,
            SmsMessageListener::class
        ],
        MemberAccountEvent::class => [
            MemberMiniMessageListener::class,
            MemberSmsMessageListener::class,
            MemberWechatMessageListener::class,
        ],
        StoreMessageEvent::class => [
            StoreMiniMessageListener::class,
            StoreSmsMessageListener::class,
            StoreWechatMessageListener::class,
            SocketMessageListener::class,
            VoiceMessageListener::class,
            WorkMessageListener::class,
            StoreWechatMessageListener::class
        ],
        MemberGiftBigEvent::class => [
            IntegralGiveListener::class,
            BalanceGiveListener::class,
            GiftBigCouponGiveListener::class,
        ],
        PayGiftEvent::class => [
            PayGiftBalanceGiveListener::class,
            PayGiftIntegralGiveListener::class,
            PayGiftCouponGiveListener::class
        ],
        OrderCollectEvent::class => [
            OrderCollectBalanceGiveListener::class,
            OrderCollectIntegralGiveListener::class,
            OrderCollectCouponGiveListener::class
        ],
        CouponEvent::class => [
            CouponMiniMessageListener::class
        ],
        SignInEvent::class => [
            SignInBalanceGiveListener::class,
            SignInIntegralGiveListener::class,
            SignInCouponGiveListener::class
        ],
        BirthdayGiftEvent::class => [
            BirthdayPackBalanceGiveListener::class,
            BirthdayPackIntegralGiveListener::class,
            BirthdayPackCouponGiveListener::class
        ],
        PartyBEvent::class => [
            PartyBCouponGiveListener::class,
            PartyBIntegralGiveListener::class
        ],
        WordCouponEvent::class => [
            WordCouponIntegralGiveListener::class,
            WordCouponBalanceGiveListener::class,
            WordCouponCouponGiveListener::class
        ],
        EquityCardEvent::class => [
            EquityCardCouponGiveListener::class
        ],
        PartnerEvent::class => [
            PayListener::class,
            RefundListener::class,
            BillListener::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
