<?php
/*
 * SDKgen is a tool to automatically generate high quality SDKs.
 * For the current version and information visit <https://sdkgen.app>
 *
 * Copyright 2020-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sdkgen\Client\Credentials;

use Sdkgen\Client\CredentialsInterface;
use Sdkgen\Client\TokenStoreInterface;

/**
 * OAuth2
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
abstract class OAuth2Abstract implements CredentialsInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $tokenUrl;
    private string $authorizationUrl;
    private ?TokenStoreInterface $tokenStore;
    private ?array $scopes;

    public function __construct(string $clientId, string $clientSecret, string $tokenUrl, string $authorizationUrl, ?TokenStoreInterface $tokenStore = null, ?array $scopes = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl;
        $this->authorizationUrl = $authorizationUrl;
        $this->tokenStore = $tokenStore;
        $this->scopes = $scopes;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authorizationUrl;
    }

    public function getTokenStore(): ?TokenStoreInterface
    {
        return $this->tokenStore;
    }

    public function getScopes(): ?array
    {
        return $this->scopes;
    }
}
