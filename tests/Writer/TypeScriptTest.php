<?php

declare(strict_types=1);

/*
 * This file is part of the App JS Bundle package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\AppJsBundle\Tests\Writer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Writer\TypeScript;

final class TypeScriptTest extends TestCase
{
    #[DataProvider('quoteProvider')]
    public function testQuotesAsBiomeDoes(string $value, string $expected): void
    {
        self::assertSame($expected, TypeScript::quote($value));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function quoteProvider(): iterable
    {
        yield 'plain' => ['draft', "'draft'"];
        yield 'single quote' => ["it's", '"it\'s"'];
        yield 'double quote' => ['say "hi"', "'say \"hi\"'"];
        yield 'as many of both' => ["'\"", "'\\'\"'"];
        yield 'backslash' => ['a\\b', "'a\\\\b'"];
    }

    public function testQuotesTheKeysThatAreNoIdentifiers(): void
    {
        self::assertSame('startDate', TypeScript::key('startDate'));
        self::assertSame('_token', TypeScript::key('_token'));
        self::assertSame("'start-date'", TypeScript::key('start-date'));
        self::assertSame("'0'", TypeScript::key('0'));
    }
}
