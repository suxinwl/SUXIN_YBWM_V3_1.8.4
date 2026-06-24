<?php

namespace App\Models\Wechat\Pay;

use App\Models\Wechat\Kernel\Contracts\Server as ServerInterface;
use App\Models\Wechat\Kernel\Exceptions\RuntimeException;
use App\Models\Wechat\Kernel\HttpClient\RequestUtil;
use App\Models\Wechat\Kernel\ServerResponse;
use App\Models\Wechat\Kernel\Support\AesGcm;
use App\Models\Wechat\Kernel\Traits\InteractWithHandlers;
use App\Models\Wechat\Pay\Contracts\Merchant as MerchantInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/App\Models\Wechatpay/App\Models\Wechatpay4_1.shtml
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_5.shtml
 */
class Server implements ServerInterface
{
    use InteractWithHandlers;
    protected ServerRequestInterface $request;

    /**
     * @throws \Throwable
     */
    public function __construct(
        protected MerchantInterface $merchant,
        ?ServerRequestInterface $request,
    ) {
        $this->request = $request ?? RequestUtil::createDefaultServerRequest();
    }

    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     * @throws \App\Models\Wechat\Kernel\Exceptions\RuntimeException
     */
    public function serve(): ResponseInterface
    {
        $message = $this->getRequestMessage();

        try {
            $defaultResponse = new Response(200, [], \strval(\json_encode(['code' => 'SUCCESS', 'message' => '成功'], JSON_UNESCAPED_UNICODE)));
            $response = $this->handle($defaultResponse, $message);

            if (!($response instanceof ResponseInterface)) {
                $response = $defaultResponse;
            }

            return ServerResponse::make($response);
        } catch (\Exception $e) {
            return new Response(
                500,
                [],
                \strval(\json_encode(['code' => 'ERROR', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE))
            );
        }
    }

    /**
     * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_5.shtml
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     */
    public function handlePaid(callable $handler): static
    {
        $this->with(function (Message $message, \Closure $next) use ($handler): mixed {
            return $message->getEventType() === 'TRANSACTION.SUCCESS' && $message->trade_state === 'SUCCESS'
                ? $handler($message, $next) : $next($message);
        });

        return $this;
    }

    /**
     * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_11.shtml
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     */
    public function handleRefunded(callable $handler): static
    {
        $this->with(function (Message $message, \Closure $next) use ($handler): mixed {
            return in_array($message->getEventType(), [
                'REFUND.SUCCESS',
                'REFUND.ABNORMAL',
                'REFUND.CLOSED',
            ]) ? $handler($message, $next) : $next($message);
        });

        return $this;
    }

    /**
     * @throws \App\Models\Wechat\Kernel\Exceptions\InvalidArgumentException
     * @throws \App\Models\Wechat\Kernel\Exceptions\RuntimeException
     */
    public function getRequestMessage(?ServerRequestInterface $request = null): \App\Models\Wechat\Kernel\Message
    {
        $originContent = ($request ?? $this->request)->getBody()->getContents();
        $attributes = \json_decode($originContent, true);

        if (!\is_array($attributes)) {
            throw new RuntimeException('Invalid request body.');
        }

        if (empty($attributes['resource']['ciphertext'])) {
            throw new RuntimeException('Invalid request.');
        }

        $attributes = \json_decode(
            AesGcm::decrypt(
                $attributes['resource']['ciphertext'],
                $this->merchant->getSecretKey(),
                $attributes['resource']['nonce'],
                $attributes['resource']['associated_data'],
            ),
            true
        );

        if (!\is_array($attributes)) {
            throw new RuntimeException('Failed to decrypt request message.');
        }

        return new Message($attributes, $originContent);
    }
}
