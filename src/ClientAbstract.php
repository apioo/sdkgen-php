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

use GuzzleHttp\Client;
use Sdkgen\Client\Exception\Authenticator\InvalidCredentialsException;

/**
 * ClientAbstract
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
abstract class ClientAbstract
{
    public const USER_AGENT = 'SDKgen';

    protected AuthenticatorInterface $authenticator;
    protected Client $httpClient;
    protected Parser $parser;

    /**
     * @throws InvalidCredentialsException
     */
    public function __construct(string $baseUrl, ?CredentialsInterface $credentials = null, ?string $version = null)
    {
        $this->authenticator = AuthenticatorFactory::factory($credentials ?? new Credentials\Anonymous());
        $this->httpClient = (new HttpClientFactory($this->authenticator, $version))->factory();
        $this->parser = new Parser($baseUrl);
    }

    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }
}
