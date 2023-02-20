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
    public const USER_AGENT = 'SDKgen Client v1.0';

    protected Client $httpClient;
    protected Parser $parser;

    /**
     * @throws InvalidCredentialsException
     */
    public function __construct(string $baseUrl, ?CredentialsInterface $credentials = null)
    {
        $this->httpClient = (new HttpClientFactory(AuthenticatorFactory::factory($credentials ?? new Credentials\Anonymous())))->factory();
        $this->parser = new Parser($baseUrl);
    }
}
