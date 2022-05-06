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
use PSX\Schema\SchemaManager;
use PSX\Schema\SchemaTraverser;
use PSX\Schema\Visitor\TypeVisitor;

/**
 * ResourceAbstract
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
abstract class ResourceAbstract
{
    protected string $baseUrl;
    protected Client $httpClient;
    protected SchemaManager $schemaManager;

    public function __construct(string $baseUrl, ?Client $httpClient = null, ?SchemaManager $schemaManager = null)
    {
        $this->baseUrl = $baseUrl;
        $this->httpClient = $httpClient ?? new Client();
        $this->schemaManager = $schemaManager ?? new SchemaManager();
    }

    protected function parse(string $data, ?string $class)
    {
        $data = Parser::decode($data);
        if ($class !== null) {
            $schema = $this->schemaManager->getSchema($class);
            return (new SchemaTraverser(false))->traverse($data, $schema, new TypeVisitor());
        } else {
            return $data;
        }
    }
}
