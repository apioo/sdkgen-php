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

use PSX\DateTime\LocalDate;
use PSX\DateTime\LocalDateTime;
use PSX\DateTime\LocalTime;
use PSX\Json\Parser as JsonParser;
use PSX\Record\RecordableInterface;
use PSX\Schema\Exception\InvalidSchemaException;
use PSX\Schema\Exception\ValidationException;
use PSX\Schema\Schema;
use PSX\Schema\SchemaManager;
use PSX\Schema\SchemaTraverser;
use PSX\Schema\TypeFactory;
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

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $this->normalizeBaseUrl($baseUrl);
        $this->schemaManager = new SchemaManager();
    }

    public function url(string $path, array $parameters): string
    {
        return $this->baseUrl . '/' . $this->substituteParameters($path, $parameters);
    }

    /**
     * @throws ParseException
     */
    public function parse(string $data, string $class, bool $isMap = false, bool $isArray = false): mixed
    {
        try {
            $data = JsonParser::decode($data);

            $schema = $this->schemaManager->getSchema($class);
            if ($isMap) {
                $schema = new Schema(TypeFactory::getMap($schema->getType()), $schema->getDefinitions());
            } elseif ($isArray) {
                $schema = new Schema(TypeFactory::getArray($schema->getType()), $schema->getDefinitions());
            }

            return (new SchemaTraverser(false))->traverse($data, $schema, new TypeVisitor());
        } catch (\JsonException $e) {
            throw new ParseException('The server returned an in valid JSON format: ' . $e->getMessage(), 0, $e);
        } catch (InvalidSchemaException $e) {
            throw new ParseException('The provided schema is invalid: ' . $e->getMessage());
        } catch (ValidationException $e) {
            throw new ParseException('The provided JSON data does not match the schema: ' . $e->getMessage(), 0, $e);
        }
    }

    public function query(array $parameters, array $structNames = []): array
    {
        $result = [];
        foreach ($parameters as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (in_array($name, $structNames)) {
                if ($value instanceof RecordableInterface) {
                    foreach ($value->toRecord()->getAll() as $nestedName => $nestedValue) {
                        $result[$nestedName] = $this->toString($nestedValue);
                    }
                }
            } else {
                $result[$name] = $this->toString($value);
            }
        }

        return $result;
    }

    private function substituteParameters(string $path, array $parameters): string
    {
        $parts = explode('/', $path);
        $result = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $name = null;
            if (str_starts_with($part, ':')) {
                $name = substr($part, 1);
            } elseif (str_starts_with($part, '$')) {
                $pos  = strpos($part, '<');
                $name = $pos !== false ? substr($part, 1, $pos - 1) : substr($part, 1);
            } elseif (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $name = substr($part, 1, -1);
            }

            if ($name !== null && array_key_exists($name, $parameters)) {
                $part = $this->toString($parameters[$name]);
            }

            $result[] = $part;
        }

        return implode('/', $result);
    }

    private function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        } elseif (is_float($value)) {
            return '' . $value;
        } elseif (is_int($value)) {
            return '' . $value;
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif ($value instanceof LocalDate) {
            return $value->toString();
        } elseif ($value instanceof LocalTime) {
            return $value->toString();
        } elseif ($value instanceof LocalDateTime) {
            return $value->toString();
        } elseif ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::RFC3339);
        } else {
            return "";
        }
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/');
    }
}
