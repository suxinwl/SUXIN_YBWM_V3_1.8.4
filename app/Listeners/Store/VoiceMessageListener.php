<?php

namespace App\Listeners\Store;

use App\Events\MemberRegisteredEvent;
use App\Events\OrderMessageEvent;
use App\Events\StoreMessageEvent;
use App\Models\Hardware;
use App\Models\MemberAccountLog;
use App\Models\Voice;
use App\Services\ConfigService;
use App\Services\MemberAccountService;
use App\Services\SwooleJobService;
use App\Services\VoiceService;
use Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
// implements ShouldQueue
class VoiceMessageListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MemberRegisteredEvent  $event
     * @return void
     */
    public function handle(StoreMessageEvent $event)
    {
        try {
            $order = $event->order;
            $voiceType = $event->type;
            $list = Hardware::where('uniacid', $order->uniacid)
                ->where('storeId', $order->storeId)
                ->where('display', 1)
                ->where('type', 3)
                ->get();
            collect($list)->map(function ($voice) use ($voiceType, $order) {
                if ($voiceType == 'complete') {
                    $voiceType = 'invoicing';
                }
                if (isset($voice->config['rule']) && isset($voice->config['sn']) && isset($voice->config['rule'][$voiceType])) {
                    $money =  $order->subOrder->money ?? $order->money ?? '';
                    $sellMoney =  $order->subOrder->sellMoney ?? $order->sellMoney ?? '';
                    $pickNo = intToN2c($order->subOrder->pickNo ?? $order->pickNo);
                    $tabel = $order->subOrder->table->name ?? $order->name;
                    if ($order->type == 1 || $order->orderIndex->type == 1) {
                        $type =   $order->subOrder->orderTypeFormat ?? $order->orderTypeFormat ?? "";
                    } else {
                        $type =   $order->subOrder->diningTypeFormat ?? $order->diningTypeFormat ?? "";
                    }
                    $tableName = $order->subOrder->table->name ?? $order->name;
                    $channel = "门店";
                    if ($order->payType > 0 || $order->orderIndex->payType > 0) {
                        $channel =  $order->payTypeFormat ??  $order->orderIndex->payTypeFormat  ?? "门店";
                    }
                    $queueNum = $order->serialNum ?? '';
                    $content = $voice->config['rule'][$voiceType]['content'];
                    eval("\$content=\"{$content}\";");
                    $num = $voice->config['rule'][$voiceType]['num'] ?? 1;
                    for ($i = 0; $i < $num; $i++) {
                        if ($voice->config['model'] == 2) {
                            $data = [
                                'agent_id' => '202007291001',
                                'agent_secret' => "11476900311476900311476900311111",
                                'msg' => $content,
                                'sbx_id' => $voice->config['sn']
                            ];
                            $res = Http::asJson()->post('http://iot.solomo-info.com:9306/admin/common/msgpush', $data)->getBody()->getContents();
                            Log::error($res);
                        } else {
                            $data = [
                                'cmd' => 'voice',
                                'msg' => $content,
                                'msgid' => time() . rand(1, 999999)
                            ];
                            if (substr($voice->config['sn'], 1, 2) == 'MS') {
                                $url = 'http://cs.mqlinks.com/txmsgpush/';
                                $map = [
                                    'sbx_id' => $voice->config['sn'],
                                    'agent_id' => json_encode($data, true),
                                ];
                            } else {
                                $url = 'http://cs.mqlinks.com/msgpush2/qos0.php';
                                $map = [
                                    'sbx_id' => $voice->config['sn'],
                                    'agent_id' => base64_encode(json_encode($data, true)),
                                ];
                            }
                            $res = Http::asJson()->post($url, $map)->body();
                        }
                    }
                }
            });
            $config = ConfigService::getChannelConfig('voice', $order->uniacid,$order->store->isolateStore);
            $config = collect($config)->toArray();
            if ($config && $config['rule'][$voiceType] && $config && $config['rule'][$voiceType]['state']) {
                $money =  $order->subOrder->money ?? $order->money ?? '';
                $sellMoney =  $order->subOrder->sellMoney ?? $order->sellMoney ?? '';
                $pickNo = intToN2c($order->subOrder->pickNo ?? $order->pickNo);
                $tabel = $order->subOrder->table->name ?? $order->name;
                if ($order->type == 1 || $order->orderIndex->type == 1) {
                    $type =   $order->subOrder->orderTypeFormat ?? $order->orderTypeFormat ?? "";
                } else {
                    $type =   $order->subOrder->diningTypeFormat ?? $order->diningTypeFormat ?? "";
                }
                $tableName = $order->subOrder->table->name ?? $order->name;
                $channel = "门店";
                $queueNum = $order->serialNum ?? '';
                if ($order->payType > 0 || $order->orderIndex->payType > 0) {
                    $channel =  $order->payTypeFormat ??  $order->orderIndex->payTypeFormat  ?? "门店";
                }
                $content = $config['rule'][$voiceType]['content'];
                eval("\$content=\"{$content}\";");
                $num = $config['rule'][$voiceType]['num'] ?? 1;
                $msg = [
                    "type" => "voice",
                    "msg" => [
                        'type' => $voiceType,
                        'name' => $voiceType,
                        'num' => $num,
                        'pickNo' => $order->subOrder->pickNo ?? $order->pickNo,
                        'voiceUrl' => VoiceService::text2audio($content, $order->uniacid)
                    ]
                ];
                SwooleJobService::sendMessage($order->uniacid, $order->storeId, json_encode($msg, 320));
            }
        } catch (\Exception $e) {
            return true;
        }
    }
}
