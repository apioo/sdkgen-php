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

use Sdkgen\Client\Exception\Authenticator\InvalidCredentialsException;

/**
 * AuthenticatorFactory
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class AuthenticatorFactory
{
    /**
     * @throws InvalidCredentialsException
     */
    public static function factory(CredentialsInterface $credentials): AuthenticatorInterface
    {
        if ($credentials instanceof Credentials\HttpBasic) {
            return new Authenticator\HttpBasic($credentials);
        } elseif ($credentials instanceof Credentials\HttpBearer) {
            return new Authenticator\HttpBearer($credentials);
        } elseif ($credentials instanceof Credentials\ApiKey) {
            return new Authenticator\ApiKey($credentials);
        } elseif ($credentials instanceof Credentials\OAuth2Abstract) {
            return new Authenticator\OAuth2($credentials);
        } elseif ($credentials instanceof Credentials\Anonymous) {
            return new Authenticator\Anonymous($credentials);
        } else {
            throw new InvalidCredentialsException('Could not find authenticator for credentials: ' . get_class($credentials));
        }
    }
}
