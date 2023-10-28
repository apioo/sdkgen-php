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

namespace Sdkgen\Client;

use Sdkgen\Client\Exception\Authenticator\InvalidAccessTokenException;

/**
 * AccessToken
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class AccessToken
{
    private string $accessToken;
    private string $tokenType;
    private int $expiresIn;
    private string $refreshToken;
    private string $scope;

    public function __construct(string $accessToken, string $tokenType, int $expiresIn, string $refreshToken, string $scope)
    {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
        $this->refreshToken = $refreshToken;
        $this->scope = $scope;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function hasRefreshToken(): bool
    {
        return !empty($this->refreshToken);
    }

    public function getExpiresInTimestamp(): int
    {
        $nowTimestamp = time();

        $expiresIn = $this->getExpiresIn();
        if ($expiresIn < 529196400) {
            // in case the expires in is lower than 1986-10-09 we assume that the field represents the duration in seconds
            // otherwise it is probably a timestamp
            $expiresIn = $nowTimestamp + $expiresIn;
        }

        return $expiresIn;
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_token' => $this->refreshToken,
            'scope' => $this->scope,
        ];
    }

    /**
     * @throws InvalidAccessTokenException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['access_token'])) {
            throw new InvalidAccessTokenException('Key "access_token" not available');
        }

        if (!isset($data['token_type'])) {
            throw new InvalidAccessTokenException('Key "token_type" not available');
        }

        if (!isset($data['expires_in'])) {
            throw new InvalidAccessTokenException('Key "expires_in" not available');
        }

        return new self(
            $data['access_token'],
            $data['token_type'],
            (int) $data['expires_in'],
            $data['refresh_token'] ?? '',
            $data['scope'] ?? ''
        );
    }
}
