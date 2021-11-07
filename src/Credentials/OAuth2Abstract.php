<?php
/*
 * SDKgen is a tool to automatically generate high quality SDKs.
 * For the current version and information visit <https://sdkgen.app>
 *
 * Copyright 2020-2021 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sdkgen\Client\Credentials;

use Sdkgen\Client\CredentialsInterface;

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
    private string $refreshUrl;

    public function __construct(string $clientId, string $clientSecret, string $tokenUrl, string $authorizationUrl, string $refreshUrl)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl;
        $this->authorizationUrl = $authorizationUrl;
        $this->refreshUrl = $refreshUrl;
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

    public function getRefreshUrl(): string
    {
        return $this->refreshUrl;
    }
}