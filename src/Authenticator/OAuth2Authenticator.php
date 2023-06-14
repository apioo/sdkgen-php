<?php
/*
 * SDKgen is a tool to automatically generate high quality SDKs.
 * For the current version and information visit <https://sdkgen.app>
 *
 * Copyright 2020-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sdkgen\Client\Authenticator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PSX\Uri\Url;
use Sdkgen\Client\AccessToken;
use Sdkgen\Client\AuthenticatorFactory;
use Sdkgen\Client\AuthenticatorInterface;
use Sdkgen\Client\Credentials;
use Sdkgen\Client\CredentialsInterface;
use Sdkgen\Client\Exception\Authenticator\AccessTokenRequestException;
use Sdkgen\Client\Exception\Authenticator\FoundNoAccessTokenException;
use Sdkgen\Client\Exception\Authenticator\InvalidAccessTokenException;
use Sdkgen\Client\Exception\Authenticator\InvalidCredentialsException;
use Sdkgen\Client\HttpClientFactory;
use Sdkgen\Client\TokenStore\MemoryTokenStore;
use Sdkgen\Client\TokenStoreInterface;

/**
 * OAuth2Authenticator
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class OAuth2Authenticator implements AuthenticatorInterface
{
    private const EXPIRE_THRESHOLD = 60 * 10;

    private Credentials\OAuth2 $credentials;
    private TokenStoreInterface $tokenStore;
    private ?array $scopes;

    public function __construct(Credentials\OAuth2 $credentials)
    {
        $this->credentials = $credentials;
        $this->tokenStore = $credentials->getTokenStore() ?? new MemoryTokenStore();
        $this->scopes = $credentials->getScopes();
    }

    /**
     * @throws AccessTokenRequestException
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     */
    public function __invoke(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->getAccessToken());
    }

    /**
     * To follow the authorization code flow you need to redirect your user to the authorization url. After successful
     * authentication and authorization of the request the user gets redirected back to your application. You can either
     * provide the redirect url at this method or you can leave this also null in case you have configured the redirect
     * url already at the app
     *
     * This method constructs a redirect url where you can redirect your user to grant access
     */
    public function buildRedirectUrl(?string $redirectUrl = null, ?array $scopes = [], ?string $state = null): string
    {
        $parameters = [
            'response_type' => 'code',
            'client_id' => $this->credentials->getClientId(),
        ];

        if (!empty($redirectUrl)) {
            $parameters['redirect_uri'] = $redirectUrl;
        }

        if (!empty($scopes)) {
            $parameters['scope'] = implode(',', $scopes);
        } elseif (!empty($this->scopes)) {
            $parameters['scope'] = implode(',', $this->scopes);
        }

        if (!empty($state)) {
            $parameters['state'] = $state;
        }

        $url = Url::parse($this->credentials->getAuthorizationUrl());
        $url = $url->withParameters(array_merge($url->getParameters(), $parameters));

        return $url->toString();
    }

    /**
     * @throws AccessTokenRequestException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     */
    protected function fetchAccessTokenByCode(string $code): AccessToken
    {
        $credentials = new Credentials\HttpBasic($this->credentials->getClientId(), $this->credentials->getClientSecret());

        try {
            $response = $this->newHttpClient($credentials)->post($this->credentials->getTokenUrl(), [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                ]
            ]);

            return $this->parseTokenResponse($response);
        } catch (GuzzleException $e) {
            throw new AccessTokenRequestException('Could not request access token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws AccessTokenRequestException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     */
    protected function fetchAccessTokenByClientCredentials(): AccessToken
    {
        $credentials = new Credentials\HttpBasic($this->credentials->getClientId(), $this->credentials->getClientSecret());

        $parameters = [
            'grant_type' => 'client_credentials',
        ];

        if (!empty($this->scopes)) {
            $parameters['scope'] = implode(',', $this->scopes);
        }

        try {
            $response = $this->newHttpClient($credentials)->post($this->credentials->getTokenUrl(), [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => $parameters
            ]);

            return $this->parseTokenResponse($response);
        } catch (GuzzleException $e) {
            throw new AccessTokenRequestException('Could not request access token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws AccessTokenRequestException
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     */
    protected function fetchAccessTokenByRefresh(string $refreshToken): AccessToken
    {
        $credentials = new Credentials\HttpBearer($this->getAccessToken(false, 0));

        try {
            $response = $this->newHttpClient($credentials)->post($this->credentials->getTokenUrl(), [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]
            ]);

            return $this->parseTokenResponse($response);
        } catch (GuzzleException $e) {
            throw new AccessTokenRequestException('Could not request access token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws AccessTokenRequestException
     * @throws FoundNoAccessTokenException
     * @throws InvalidAccessTokenException
     * @throws InvalidCredentialsException
     */
    protected function getAccessToken(bool $automaticRefresh = true, int $expireThreshold = self::EXPIRE_THRESHOLD): string
    {
        $timestamp = time();

        $accessToken = $this->tokenStore->get();
        if (!$accessToken instanceof AccessToken || $accessToken->getExpiresIn() < $timestamp) {
            $accessToken = $this->fetchAccessTokenByClientCredentials();
        }

        if ($accessToken->getExpiresIn() > ($timestamp + $expireThreshold)) {
            return $accessToken->getAccessToken();
        }

        if ($automaticRefresh && $accessToken->getRefreshToken()) {
            $accessToken = $this->fetchAccessTokenByRefresh($accessToken->getRefreshToken());
        }

        return $accessToken->getAccessToken();
    }

    /**
     * @throws InvalidAccessTokenException
     */
    private function parseTokenResponse(ResponseInterface $response): AccessToken
    {
        if ($response->getStatusCode() !== 200) {
            throw new InvalidAccessTokenException('Could not obtain access token, received a non successful status code: ' . $response->getStatusCode());
        }

        $data = \json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            throw new InvalidAccessTokenException('Could not obtain access token');
        }

        $token = AccessToken::fromArray($data);

        $this->tokenStore->persist($token);

        return $token;
    }

    /**
     * @throws InvalidCredentialsException
     */
    private function newHttpClient(CredentialsInterface $credentials): Client
    {
        return (new HttpClientFactory(AuthenticatorFactory::factory($credentials)))->factory();
    }
}
