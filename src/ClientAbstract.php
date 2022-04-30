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

namespace Sdkgen\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PSX\Schema\SchemaManager;
use PSX\Uri\Url;
use Sdkgen\Client\Credentials\HttpBasic;
use Sdkgen\Client\Credentials\HttpBearer;
use Sdkgen\Client\Exception\FoundNoAccessTokenException;
use Sdkgen\Client\Exception\InvalidAccessTokenException;
use Sdkgen\Client\Exception\InvalidCredentialsException;
use Sdkgen\Client\TokenStore\MemoryTokenStore;

/**
 * ClientAbstract
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
abstract class ClientAbstract
{
    private const USER_AGENT = 'SDKgen Client v0.1';
    private const EXPIRE_THRESHOLD = 60 * 10;

    protected string $baseUrl;
    protected ?CredentialsInterface $credentials;
    protected TokenStoreInterface $tokenStore;
    protected SchemaManager $schemaManager;

    public function __construct(string $baseUrl, ?CredentialsInterface $credentials = null, ?TokenStoreInterface $tokenStore = null)
    {
        $this->baseUrl = $baseUrl;
        $this->credentials = $credentials;
        $this->tokenStore = $tokenStore ?? new MemoryTokenStore();
        $this->schemaManager = new SchemaManager();
    }

    /**
     * To follow the authorization code flow you need to redirect your user to the authorization url. After successful
     * authentication and authorization of the request the user gets redirected back to your application. You can either
     * provide the redirect url at this method or you can leave this also null in case you have configured the redirect
     * url already at the app
     *
     * This method constructs an redirect url where you can redirect your user to grant access
     *
     * @throws InvalidCredentialsException
     */
    public function buildRedirectUrl(?string $redirectUrl = null, ?array $scopes = [], ?string $state = null): string
    {
        if (!$this->credentials instanceof Credentials\AuthorizationCode) {
            throw new InvalidCredentialsException('The configured credentials do not support the OAuth2 authorization code flow');
        }

        $parameters = [
            'response_type' => 'code',
            'client_id' => $this->credentials->getClientId(),
        ];

        if (!empty($redirectUrl)) {
            $parameters['redirect_uri'] = $redirectUrl;
        }

        if (!empty($scopes)) {
            $parameters['scope'] = implode(',', $scopes);
        }

        if (!empty($state)) {
            $parameters['state'] = $state;
        }

        $url = new Url($this->credentials->getAuthorizationUrl());
        $url = $url->withParameters(array_merge($url->getParameters(), $parameters));

        return $url->toString();
    }

    /**
     * @param string $code
     * @return AccessToken
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function fetchAccessTokenByCode(string $code): AccessToken
    {
        if (!$this->credentials instanceof Credentials\AuthorizationCode) {
            throw new InvalidCredentialsException('The configured credentials do not support the OAuth2 authorization code flow');
        }

        $credentials = new HttpBasic($this->credentials->getClientId(), $this->credentials->getClientSecret());

        $response = $this->newHttpClient($credentials)->post($this->credentials->getTokenUrl(), [
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]
        ]);

        return $this->parseTokenResponse($response);
    }

    /**
     * @return AccessToken
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function fetchAccessTokenByClientCredentials(): AccessToken
    {
        if (!$this->credentials instanceof Credentials\ClientCredentials) {
            throw new InvalidCredentialsException('The configured credentials do not support the OAuth2 client credentials flow');
        }

        $credentials = new HttpBasic($this->credentials->getClientId(), $this->credentials->getClientSecret());

        $response = $this->newHttpClient($credentials)->post($this->credentials->getTokenUrl(), [
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ]
        ]);

        return $this->parseTokenResponse($response);
    }

    /**
     * @param string $refreshToken
     * @return AccessToken
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function fetchAccessTokenByRefresh(string $refreshToken): AccessToken
    {
        if (!$this->credentials instanceof Credentials\OAuth2Abstract) {
            throw new InvalidCredentialsException('The configured credentials do not support the OAuth2 flow');
        }

        $credentials = new HttpBearer($this->getAccessToken(false, 0));

        $response = $this->newHttpClient($credentials)->post($this->credentials->getRefreshUrl(), [
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]
        ]);

        return $this->parseTokenResponse($response);
    }

    /**
     * @param bool $automaticRefresh
     * @param int $expireThreshold
     * @return string
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getAccessToken(bool $automaticRefresh = true, int $expireThreshold = self::EXPIRE_THRESHOLD): string
    {
        if (!$this->tokenStore instanceof TokenStoreInterface) {
            throw new FoundNoAccessTokenException('No token store was configured');
        }

        $accessToken = $this->tokenStore->get();
        if (!$accessToken instanceof AccessToken) {
            throw new FoundNoAccessTokenException('Found no access token, please obtain an access token before making an request');
        }

        if ($accessToken->getExpiresIn() > (time() + $expireThreshold)) {
            return $accessToken->getAccessToken();
        }

        if ($automaticRefresh && $accessToken->getRefreshToken()) {
            return $this->fetchAccessTokenByRefresh($accessToken->getRefreshToken())->getAccessToken();
        } else {
            return $accessToken->getAccessToken();
        }
    }

    protected function newHttpClient(?CredentialsInterface $credentials = null): Client
    {
        if ($credentials === null) {
            $credentials = $this->credentials;
        }

        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        if ($credentials instanceof Credentials\HttpBasic) {
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($credentials) {
                return $request->withHeader('Authorization', 'Basic ' . base64_encode($credentials->getUserName() . ':' . $credentials->getPassword()));
            }));
        } elseif ($credentials instanceof Credentials\HttpBearer) {
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($credentials) {
                return $request->withHeader('Authorization', 'Bearer ' . $credentials->getToken());
            }));
        } elseif ($credentials instanceof Credentials\ApiKey) {
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($credentials) {
                return $request->withHeader($credentials->getName(), $credentials->getToken());
            }));
        } elseif ($this->credentials instanceof Credentials\OAuth2Abstract) {
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
                return $request->withHeader('Authorization', 'Bearer ' . $this->getAccessToken());
            }));
        }

        return new Client(['handler' => $stack]);
    }

    /**
     * @param ResponseInterface $response
     * @return AccessToken
     * @throws InvalidAccessTokenException
     */
    private function parseTokenResponse(ResponseInterface $response): AccessToken
    {
        if ($response->getStatusCode() !== 200) {
            throw new InvalidAccessTokenException('Could not obtain access token');
        }

        $data = \json_decode((string) $response->getBody());
        if (!is_array($data)) {
            throw new InvalidAccessTokenException('Could not obtain access token');
        }

        $token = AccessToken::fromArray($data);

        $this->tokenStore->persist($token);

        return $token;
    }
}
