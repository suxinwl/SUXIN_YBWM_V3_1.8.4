<?php

declare(strict_types=1);

namespace App\Models\Wechat\Make;

use App\Models\Wechat\Kernel\Contracts\AccessToken as AccessTokenInterface;
use App\Models\Wechat\Kernel\Contracts\Server as ServerInterface;
use App\Models\Wechat\Kernel\Encryptor;
use App\Models\Wechat\Kernel\Exceptions\InvalidConfigException;
use App\Models\Wechat\Kernel\HttpClient\AccessTokenAwareClient;
use App\Models\Wechat\Kernel\HttpClient\AccessTokenExpiredRetryStrategy;
use App\Models\Wechat\Kernel\HttpClient\RequestUtil;
use App\Models\Wechat\Kernel\HttpClient\Response;
use App\Models\Wechat\Kernel\Traits\InteractWithCache;
use App\Models\Wechat\Kernel\Traits\InteractWithClient;
use App\Models\Wechat\Kernel\Traits\InteractWithConfig;
use App\Models\Wechat\Kernel\Traits\InteractWithHttpClient;
use App\Models\Wechat\Kernel\Traits\InteractWithServerRequest;
use App\Models\Wechat\Make\Contracts\Account as AccountInterface;
use App\Models\Wechat\Make\Contracts\Application as ApplicationInterface;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\RetryableHttpClient;
use function array_merge;
use function is_null;
use function str_contains;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Application implements ApplicationInterface
{
    use InteractWithConfig;
    use InteractWithCache;
    use InteractWithServerRequest;
    use InteractWithHttpClient;
    use InteractWithClient;
    use LoggerAwareTrait;

    protected ?Encryptor $encryptor = null;
    protected ?ServerInterface $server = null;
    protected ?AccountInterface $account = null;
    protected ?AccessTokenInterface $accessToken = null;

    public function getAccount(): AccountInterface
    {
        if (!$this->account) {
            $this->account = new Account(
                baseUri: $this->config->get('http.base_uri'),
                appId: (string) $this->config->get('appid'),
                /** @phpstan-ignore-line */
                token: (string) $this->config->get('token'),
                /** @phpstan-ignore-line */
            );
        }

        return $this->account;
    }

    public function setAccount(AccountInterface $account): static
    {
        $this->account = $account;

        return $this;
    }


    public function getAccessToken(): AccessTokenInterface
    {
        if (!$this->accessToken) {
            $this->accessToken = new AccessToken(
                appId: $this->getAccount()->getAppId(),
                token: $this->getAccount()->getToken(),
                cache: $this->getCache(),
                httpClient: $this->getHttpClient(),
            );
        }

        return $this->accessToken;
    }

    public function setAccessToken(AccessTokenInterface $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function createClient(): AccessTokenAwareClient
    {
        $httpClient = $this->getHttpClient();

        if (!!$this->config->get('http.retry', false)) {
            $httpClient = new RetryableHttpClient(
                $httpClient,
                $this->getRetryStrategy(),
                (int) $this->config->get('http.max_retries', 2) // @phpstan-ignore-line
            );
        }

        return (new AccessTokenAwareClient(
            client: $httpClient,
            accessToken: $this->getAccessToken(),
            failureJudge: fn (
                Response $response
            ) => !!($response->toArray()['errcode'] ?? 0) || !is_null($response->toArray()['error'] ?? null),
            throw: !!$this->config->get('http.throw', true),
        ))->setPresets($this->config->all());
    }

    public function getRetryStrategy(): AccessTokenExpiredRetryStrategy
    {
        $retryConfig = RequestUtil::mergeDefaultRetryOptions((array) $this->config->get('http.retry', []));

        return (new AccessTokenExpiredRetryStrategy($retryConfig))
            ->decideUsing(function (AsyncContext $context, ?string $responseContent): bool {
                return !empty($responseContent)
                    && str_contains($responseContent, '42001')
                    && str_contains($responseContent, 'access_token expired');
            });
    }

    /**
     * @return array<string,mixed>
     */
    protected function getHttpClientDefaultOptions(): array
    {
        return array_merge(
            ['base_uri' => $this->getAccount()->getBaseUri()],
            (array) $this->config->get('http', [])
        );
    }
}
