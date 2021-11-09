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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
use Sdkgen\Client\Exception\InvalidStateException;
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
    private const JWT_ALG = 'HS256';
    private const EXPIRE_THRESHOLD = 60 * 10;

    protected ?CredentialsInterface $credentials = null;
    protected string $baseUri;
    protected TokenStoreInterface $tokenStore;
    protected SchemaManager $schemaManager;

    public function __construct(string $baseUri, ?TokenStoreInterface $tokenStore = null)
    {
        $this->baseUri = $baseUri;
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
    public function buildRedirectUrl(?string $redirectUrl = null, ?array $scopes = []): string
    {
        if (!$this->credentials instanceof Credentials\AuthorizationCode) {
            throw new InvalidCredentialsException('The configured credentials do not support the OAuth2 authorization code flow');
        }

        $state = JWT::encode(['iat' => time(), 'exp' => time() + (60 * 5)], $this->credentials->getClientSecret(), self::JWT_ALG);

        $parameters = [
            'response_type' => 'code',
            'client_id' => $this->credentials->getClientId(),
            'state' => $state,
        ];

        if (!empty($redirectUrl)) {
            $parameters['redirect_uri'] = $redirectUrl;
        }

        if (!empty($scopes)) {
            $parameters['scope'] = implode(',', $scopes);
        }

        $url = new Url($this->credentials->getAuthorizationUrl());
        $url = $url->withParameters(array_merge($url->getParameters(), $parameters));

        return $url->toString();
    }

    /**
     * @param string $code
     * @param string $state
     * @return AccessToken
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     * @throws InvalidStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function fetchAccessTokenByCode(string $code, string $state)
    {
        if (!$this->credentials instanceof Credentials\AuthorizationCode) {
            throw new InvalidCredentialsException('The configured credentials do not support the OAuth2 authorization code flow');
        }

        try {
            JWT::decode($state, new Key($this->credentials->getClientSecret(), self::JWT_ALG));
        } catch (\Exception $e) {
            throw new InvalidStateException('Provided state is invalid', 0, $e);
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

        return $this->parseResponse($response);
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

        return $this->parseResponse($response);
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

        return $this->parseResponse($response);
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
        $accessToken = $this->tokenStore->get();
        if ($accessToken instanceof AccessToken && $accessToken->getExpiresIn() > (time() + $expireThreshold)) {
            return $accessToken->getAccessToken();
        }

        if ($automaticRefresh && $accessToken->getRefreshToken()) {
            return $this->fetchAccessTokenByRefresh($accessToken->getRefreshToken())->getAccessToken();
        } else {
            throw new InvalidCredentialsException('Could not refresh token since the used token is either not available or expired, please obtain a new access token');
        }
    }

    /**
     * @param CredentialsInterface|null $credentials
     * @return Client
     */
    protected function newHttpClient(?CredentialsInterface $credentials): Client
    {
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
        } elseif ($credentials instanceof Credentials\OAuth2Abstract) {
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
    private function parseResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            throw new InvalidAccessTokenException('Could not obtain access token');
        }

        $data = \json_decode((string) $response->getBody());
        if (!is_array($data)) {
            throw new InvalidAccessTokenException('Could not obtain access token');
        }

        $token = AccessToken::fromArray($data);

        if ($this->tokenStore instanceof TokenStoreInterface) {
            $this->tokenStore->persist($token);
        }

        return $token;
    }
}
