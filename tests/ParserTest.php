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

namespace Sdkgen\Client\Tests;

use PHPUnit\Framework\TestCase;
use PSX\DateTime\Date;
use Sdkgen\Client\Parser;

/**
 * ParserTest
 *
 * @author Christoph Kappestein <christoph.kappestein@gmail.com>
 * @link   https://sdkgen.app
 */
class ParserTest extends TestCase
{
    public function testUrl()
    {
        $parser = new Parser('https://api.acme.com/');

        $this->assertEquals('https://api.acme.com/foo/bar', $parser->url('/foo/bar', []));
        $this->assertEquals('https://api.acme.com/foo/1', $parser->url('/foo/:bar', ['bar' => 1]));
        $this->assertEquals('https://api.acme.com/foo/foo', $parser->url('/foo/:bar', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/2020', $parser->url('/foo/$year<[0-9]+>', ['year' => '2020']));
        $this->assertEquals('https://api.acme.com/foo/foo', $parser->url('/foo/{bar}', ['bar' => 'foo']));
        $this->assertEquals('https://api.acme.com/foo/2023-02-21', $parser->url('/foo/:bar', ['bar' => new Date('2023-02-21')]));
    }
}
