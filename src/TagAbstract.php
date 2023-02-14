<?php
/*
 * SDKgen is a tool to automatically generate high quality SDKs.
 * For the current version and information visit <https://sdkgen.app>
 *
 * Copyright 2020-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sdkgen\Client;

use GuzzleHttp\Client;
use PSX\Json\Parser;

/**
 * TagAbstract
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
abstract class TagAbstract
{
    protected Client $httpClient;
    protected Parser $parser;

    public function __construct(Client $httpClient, Parser $parser)
    {
        $this->httpClient = $httpClient;
        $this->parser = $parser;
    }
}
