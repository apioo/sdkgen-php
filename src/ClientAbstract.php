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
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PSX\Schema\SchemaManager;
use PSX\Uri\Url;
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

    private string $baseUri;
    private TokenStoreInterface $tokenStore;
    private SchemaManager $schemaManager;
    private ?CredentialsInterface $credentials = null;
    private array $config;

    public function __construct(string $baseUri, ?TokenStoreInterface $tokenStore = null)
    {
        $this->baseUri = $baseUri;
        $this->tokenStore = $tokenStore ?? new MemoryTokenStore();
        $this->schemaManager = new SchemaManager();
        $this->config = $this->configure();
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
        if (!$this->credentials instanceof Credentials\OAuth2) {
            throw new InvalidCredentialsException('Provided no OAuth2 credentials, please set the credentials before calling this method');
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

        $url = new Url($this->config['security']['flows']['authorizationCode']['authorizationUrl']);
        $url = $url->withParameters(array_merge($url->getParameters(), $parameters));

        return $url->toString();
    }

    /**
     * Returns the configuration for this client
     *
     * @return array
     */
    abstract protected function configure(): array;

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
        if (!$this->credentials instanceof Credentials\OAuth2) {
            throw new InvalidCredentialsException('Provided no OAuth2 credentials, please set the credentials before calling this method');
        }

        try {
            JWT::decode($state, $this->credentials->getClientSecret(), [self::JWT_ALG]);
        } catch (\Exception $e) {
            throw new InvalidStateException('Provided state is invalid', 0, $e);
        }

        $response = $this->newHttpClient(null)->post($this->config['security']['flows']['authorizationCode']['tokenUrl'], [
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Authorization' => 'Basic ' . base64_encode($this->credentials->getClientId() . ':' . $this->credentials->getClientSecret()),
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => 'https://apigen.app/callback',
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
        if (!$this->credentials instanceof Credentials\OAuth2) {
            throw new InvalidCredentialsException('Provided no OAuth2 credentials, please set the credentials before calling this method');
        }

        $response = $this->newHttpClient(null)->post($this->config['security']['flows']['clientCredentials']['tokenUrl'], [
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Authorization' => 'Basic ' . base64_encode($this->credentials->getClientId() . ':' . $this->credentials->getClientSecret()),
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
        if (!$this->credentials instanceof Credentials\OAuth2) {
            throw new InvalidCredentialsException('Provided no OAuth2 credentials, please set the credentials before calling this method');
        }

        $response = $this->newHttpClient(null)->post($this->config['security']['flows']['authorizationCode']['refreshUrl'], [
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Authorization' => 'Basic ' . base64_encode($this->credentials->getClientId() . ':' . $this->credentials->getClientSecret()),
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
     * @param CredentialsInterface|null $credentials
     * @return Client
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function newHttpClient(?CredentialsInterface $credentials): Client
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        if ($this->config['security']['type'] === 'http' && $this->config['security']['scheme'] === 'basic' && $credentials instanceof Credentials\Basic) {
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($credentials) {
                return $request->withHeader('Authorization', 'Basic ' . base64_encode($credentials->getUserName() . ':' . $credentials->getPassword()));
            }));
        } elseif ($this->config['security']['type'] === 'http' && $this->config['security']['scheme'] === 'bearer' && $credentials instanceof Credentials\Token) {
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($credentials) {
                return $request->withHeader('Authorization', 'Bearer ' . $credentials->getToken());
            }));
        } elseif ($this->config['security']['type'] === 'apiKey' && $this->config['security']['in'] == 'header' && $credentials instanceof Credentials\Token) {
            $name = $this->config['security']['name'];
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($credentials, $name) {
                return $request->withHeader($name, $credentials->getToken());
            }));
        } elseif ($this->config['security']['type'] === 'oauth2' && $credentials instanceof Credentials\OAuth2) {
            $accessToken = $this->tokenStore->get();
            if (!$accessToken instanceof AccessToken) {
                throw new FoundNoAccessTokenException('No access token was obtained, please obtain an access token before making an request');
            }

            // in case the token is expired try to obtain a new token in case a refresh token is available
            if ($accessToken->getExpiresIn() < time() && $accessToken->getRefreshToken()) {
                $accessToken = $this->fetchAccessTokenByRefresh($accessToken->getRefreshToken());
            }

            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($accessToken) {
                return $request->withHeader('Authorization', 'Bearer ' . $accessToken->getAccessToken());
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
