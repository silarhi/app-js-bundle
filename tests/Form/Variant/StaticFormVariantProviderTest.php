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

namespace Silarhi\AppJsBundle\Tests\Form\Variant;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Form\Variant\FormVariant;
use Silarhi\AppJsBundle\Form\Variant\StaticFormVariantProvider;

final class StaticFormVariantProviderTest extends TestCase
{
    public function testGivesTheVariantsByName(): void
    {
        $variants = [...(new StaticFormVariantProvider([
            'company' => ['options' => ['with_siren' => true]],
            'empty' => [],
            'with data' => ['data' => ['a' => 1]],
        ]))->getVariants()];

        self::assertEquals([
            'company' => new FormVariant(['with_siren' => true]),
            'empty' => new FormVariant(),
            'with data' => new FormVariant(data: ['a' => 1]),
        ], $variants);
    }

    /**
     * @param array<array-key, mixed> $variants
     */
    #[DataProvider('invalidProvider')]
    public function testRefusesInvalidVariants(array $variants, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        StaticFormVariantProvider::validate($variants);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'not an array' => [['company' => true], 'The variant "company" must be an array, "bool" given.'];
        yield 'unknown key' => [[['option' => []]], 'The variant "0" has unknown keys "option": only "options" and "data" are allowed.'];
        yield 'options not an array' => [['company' => ['options' => 'with_siren']], 'The options of the variant "company" must be an array, "string" given.'];
    }
}
