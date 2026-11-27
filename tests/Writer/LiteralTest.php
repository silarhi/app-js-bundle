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

use const INF;
use const NAN;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Writer\Literal;

final class LiteralTest extends TestCase
{
    #[DataProvider('floatProvider')]
    public function testRendersFloatsAsJavaScriptReadsThem(float $value, string $expected): void
    {
        self::assertSame($expected, Literal::render($value));
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function floatProvider(): iterable
    {
        yield 'decimal' => [1.5, '1.5'];
        yield 'integral' => [2.0, '2'];
        yield 'exponent' => [1.0e25, '1.0e+25'];
        yield 'infinity' => [INF, 'Infinity'];
        yield 'negative infinity' => [-INF, '-Infinity'];
        yield 'not a number' => [NAN, 'NaN'];
    }

    public function testRendersTheFloatsOfAnArray(): void
    {
        self::assertSame('Object.freeze([1.5, Infinity, NaN])', Literal::render([1.5, INF, NAN]));
    }
}
