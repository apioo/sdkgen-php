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
use PSX\Schema\SchemaManager;
use Sdkgen\Client\Exception\Authenticator\InvalidCredentialsException;

/**
 * ClientAbstract
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
abstract class ClientAbstract
{
    public const USER_AGENT = 'SDKgen Client v1.0';

    protected string $baseUrl;

    protected AuthenticatorInterface $authenticator;
    protected Client $httpClient;
    protected Parser $parser;

    /**
     * @throws InvalidCredentialsException
     */
    public function __construct(string $baseUrl, ?CredentialsInterface $credentials = null)
    {
        $this->baseUrl = $baseUrl;
        $this->authenticator = AuthenticatorFactory::factory($credentials ?? new Credentials\Anonymous());
        $this->httpClient = (new HttpClientFactory($this->authenticator))->factory();
        $this->parser = new Parser($baseUrl);
    }

    /**
     * The authenticator is a service which is able to obtain an access token i.e. via an OAuth2 flow. If you provide
     * already a fix access token like a bearer token you probably don`t need this service, it is only needed in case
     * you want to obtain an access token
     */
    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }
}
