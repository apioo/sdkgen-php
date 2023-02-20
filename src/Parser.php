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

use PSX\Json\Parser as JsonParser;
use PSX\Schema\Exception\InvalidSchemaException;
use PSX\Schema\Exception\ValidationException;
use PSX\Schema\SchemaManager;
use PSX\Schema\SchemaTraverser;
use PSX\Schema\Visitor\TypeVisitor;
use Sdkgen\Client\Exception\ParseException;

/**
 * Parser
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class Parser
{
    private string $baseUrl;
    private SchemaManager $schemaManager;

    public function __construct(string $baseUrl, SchemaManager $schemaManager)
    {
        $this->baseUrl = $baseUrl;
        $this->schemaManager = $schemaManager;
    }

    public function url(string $path, array $parameters): string
    {
        return $this->substituteParameters(rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/'), $parameters);
    }

    /**
     * @throws ParseException
     */
    public function parse(string $data, string $class): mixed
    {
        try {
            $data = JsonParser::decode($data);
            $schema = $this->schemaManager->getSchema($class);
            return (new SchemaTraverser(false))->traverse($data, $schema, new TypeVisitor());
        } catch (\JsonException $e) {
            throw new ParseException('The server returned an in valid JSON format: ' . $e->getMessage(), 0, $e);
        } catch (InvalidSchemaException $e) {
            throw new ParseException('The provided schema is invalid: ' . $e->getMessage());
        } catch (ValidationException $e) {
            throw new ParseException('The provided JSON data does not match the schema: ' . $e->getMessage(), 0, $e);
        }
    }

    public function query(array $parameters): array
    {
        $result = [];
        foreach ($parameters as $name => $value) {
            if ($value === null) {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::RFC3339);
            }

            $result[$name] = $value;
        }

        return $result;
    }

    private function substituteParameters(string $url, array $parameters): string
    {
        foreach ($parameters as $name => $value) {
            if ($value === null) {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::RFC3339);
            }

            $url = str_replace(':' . $name, $value, $url);
        }

        return $url;
    }
}
