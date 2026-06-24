<?php

declare(strict_types=1);

namespace App\Models\Wechat\WaiSongBang;

use App\Models\Wechat\Kernel\HttpClient\HttpClientMethods;
use App\Models\Wechat\Kernel\HttpClient\RequestUtil;
use App\Models\Wechat\Kernel\HttpClient\RequestWithPresets;
use App\Models\Wechat\Kernel\Exceptions\BadRequestException;
use App\Models\Wechat\WaiSongBang\Store;
use App\Models\Wechat\Kernel\HttpClient\Response;
use App\Models\Wechat\Kernel\Support\UserAgent;
use Mockery\Mock;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Models\Wechat\Kernel\Traits\MockableHttpClient;
use Illuminate\Support\Facades\Log;

/**
 * @method ResponseInterface get(string $uri, array $options = [])
 * @method ResponseInterface post(string $uri, array $options = [])
 * @method ResponseInterface put(string $uri, array $options = [])
 * @method ResponseInterface patch(string $uri, array $options = [])
 * @method ResponseInterface delete(string $uri, array $options = [])
 */
class Client implements HttpClientInterface
{
    use DecoratorTrait {
        DecoratorTrait::withOptions insteadof HttpClientTrait;
    }
    use HttpClientTrait;
    use HttpClientMethods;
    use MockableHttpClient;
    use RequestWithPresets;



    /**
     * @var array<string, mixed>
     */
    protected array $defaultOptions = [
        'base_uri' => 'https://e.waisongbang.com',
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
    ];

    protected bool $throw = true;
    protected $config = [];

    /**
     * @param  array<string, mixed> $defaultOptions
     */
    public function __construct(protected Merchant $merchant, ?HttpClientInterface $client = null, array $defaultOptions = [])
    {
        $this->throw = !!($defaultOptions['throw'] ?? true);

        $this->defaultOptions = array_merge(self::OPTIONS_DEFAULTS, $this->defaultOptions);

        if (!empty($defaultOptions)) {
            $defaultOptions = RequestUtil::formatDefaultOptions($this->defaultOptions);
            [, $this->defaultOptions] = self::prepareRequest(null, null, $defaultOptions, $this->defaultOptions);
        }
        $this->client = ($client ?? SymfonyHttpClient::create())->withOptions($this->defaultOptions);
    }

    /**
     * @param  array<string, array|mixed>  $options
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Exception
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (empty($options['headers'])) {
            $options['headers'] = [];
        }
        if (isset($options['body']) || isset($options['json'])) {
            $params = $options['body'] ?? $options['json'];
        } else {
            $params = [];
        }
        $body = array_merge(array(
            'app_key' => $this->merchant->getAppKey(),
            'timestamp' => time(),
            'version' => '1.0',
            'third_partner_id' => $this->merchant->getThirdPartnerId()
        ), $params);
        $body['sign'] = $this->createSignature($body);
        $options['json'] = $body;
        $options['headers']['User-Agent'] = UserAgent::create();
        $options = RequestUtil::formatOptions($options, $method);
        [, $options] = $this->prepareRequest($method, $url, $options, $this->defaultOptions, true);
        return new Response($this->client->request($method, $url, $options), throw: $this->throw);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->client->$name(...$arguments);
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws \Exception
     */
    public function createSignature($body): string
    {
        return (new Signature($this->merchant))->createHeader($body);
    }
}
