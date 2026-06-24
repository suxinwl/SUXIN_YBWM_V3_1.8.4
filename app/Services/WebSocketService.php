<?php

namespace App\Services;

use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Redis;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService implements WebSocketHandlerInterface
{
    /**@var \Swoole\Table $wsTable */
    private $wsTable;
    public function __construct()
    {
        $this->wsTable = app('swoole')->wsTable;
    }
    // 场景：WebSocket中UserId与FD绑定
    public function onOpen(Server $server, Request $request)
    {
        // var_dump(app('swoole') === $server);// 同一实例
        /**
         * 获取当前登录的用户
         * 此特性要求建立WebSocket连接的路径要经过Authenticate之类的中间件。
         * 例如：
         * 浏览器端：var ws = new WebSocket("ws://127.0.0.1:5200/ws");
         * 那么Laravel中/ws路由就需要加上类似Authenticate的中间件。
         * Route::get('/ws', function () {
         *     // 响应状态码200的任意内容
         *     return 'websocket';
         * })->middleware(['auth']);
         */
        // $user = Auth::user();
        // $userId = $user ? $user->id : 0; // 0 表示未登录的访客用户
        $uniacid = $request->header['uniacid'] ?? $request->get['uniacid'] ?? 0;
        $storeId = $request->header['storeid'] ?? $request->get['storeId'] ?? 0;
        $userAgree = $request->header['appType'] ?? 'pc';
        // if (!$user) {
        //     // 未登录用户直接断开连接
        //     $server->push($request->fd, json_encode(['type' => 'login', 'msg' => "用户信息错误"]));
        //     $server->disconnect($request->fd);
        //     return;
        // }
        if ($uniacid && empty($storeId)) {
            Redis::SADD("wsTable:uniacid:$uniacid", $request->fd);
        }
        if ($storeId) {
            Redis::SADD("wsTable:store:$storeId", $request->fd);
        }
        $this->wsTable->set("fd:$request->fd", ['value' => json_encode(['uniacid' => $uniacid, 'storeId' => $storeId, 'appType' => $userAgree])]);
        $server->push($request->fd, json_encode(['type' => 'login', 'msg' => "Welcome $request->fd"]));
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // foreach ($this->wsTable as $key => $row) {
        //     if ($server->isEstablished(intval($row['value']))) {
        //         $server->push($row['value'], $frame->data);
        //     }
        // }
        $server->push($frame->fd, json_encode(['type' => 'ping', 'msg' => 'ok'], 320));
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        $data = $this->wsTable->get('fd:' . $fd);
        $data = json_decode($data['value'], true);
        if (Redis::sismember('wsTable:uniacid:' . $data['uniacid'], $fd)) {
            Redis::srem('wsTable:uniacid:' . $data['uniacid'], $fd);
        }
        if (Redis::sismember('wsTable:store:' . $data['storeId'], $fd)) {
            Redis::srem('wsTable:store:' . $data['storeId'], $fd);
        }
        $this->wsTable->del('fd:' . $fd);
        $server->push($fd, json_encode(['type' => 'logout', 'msg' => "Goodbye # "]));
    }
}
