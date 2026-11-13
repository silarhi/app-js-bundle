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
use Silarhi\AppJsBundle\Form\Variant\FormVariantProviderInterface;
use Silarhi\AppJsBundle\Form\Variant\MatrixFormVariantProvider;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Kind;
use stdClass;

final class MatrixFormVariantProviderTest extends TestCase
{
    public function testBuildsEachCombinationWithTheOptions(): void
    {
        $variants = iterator_to_array((new MatrixFormVariantProvider(
            ['profile' => 'landlord'],
            ['kind' => [Kind::Text, Kind::Date], 'with_siren' => [true, false]],
        ))->getVariants());

        self::assertEquals([
            'kind: Kind::Text, with_siren: true' => new FormVariant(['profile' => 'landlord', 'kind' => Kind::Text, 'with_siren' => true]),
            'kind: Kind::Text, with_siren: false' => new FormVariant(['profile' => 'landlord', 'kind' => Kind::Text, 'with_siren' => false]),
            'kind: Kind::Date, with_siren: true' => new FormVariant(['profile' => 'landlord', 'kind' => Kind::Date, 'with_siren' => true]),
            'kind: Kind::Date, with_siren: false' => new FormVariant(['profile' => 'landlord', 'kind' => Kind::Date, 'with_siren' => false]),
        ], $variants);
    }

    public function testBuildsOnceWithTheOptionsAlone(): void
    {
        $variants = iterator_to_array((new MatrixFormVariantProvider(['view' => 'full']))->getVariants());

        self::assertEquals(['default' => new FormVariant(['view' => 'full'])], $variants);
    }

    public function testNamesEachValue(): void
    {
        $variants = iterator_to_array((new MatrixFormVariantProvider(matrix: ['value' => [null, 'full', 12, 1.5, new stdClass()]]))->getVariants());

        self::assertSame(['value: null', "value: 'full'", 'value: 12', 'value: 1.5', 'value: stdClass'], array_keys($variants));
    }

    public function testBuildsEachVariantOfTheProviderWithEachCombination(): void
    {
        $provider = new class implements FormVariantProviderInterface {
            public function getVariants(): iterable
            {
                yield 'furnished' => new FormVariant(['furnished' => true, 'profile' => 'tenant'], ['a' => 1]);
            }
        };

        $variants = iterator_to_array((new MatrixFormVariantProvider(
            ['profile' => 'landlord'],
            ['with_bank' => [true, false]],
            $provider,
        ))->getVariants());

        // The options of the variant win
        self::assertEquals([
            'furnished, with_bank: true' => new FormVariant(['profile' => 'tenant', 'with_bank' => true, 'furnished' => true], ['a' => 1]),
            'furnished, with_bank: false' => new FormVariant(['profile' => 'tenant', 'with_bank' => false, 'furnished' => true], ['a' => 1]),
        ], $variants);
    }

    public function testKeepsTheNamesOfTheProviderWithoutMatrix(): void
    {
        $provider = new class implements FormVariantProviderInterface {
            public function getVariants(): iterable
            {
                yield 'furnished' => new FormVariant();
            }
        };

        $variants = iterator_to_array((new MatrixFormVariantProvider(['profile' => 'landlord'], [], $provider))->getVariants());

        self::assertEquals(['furnished' => new FormVariant(['profile' => 'landlord'])], $variants);
    }

    public function testAcceptsTheMostCombinations(): void
    {
        MatrixFormVariantProvider::validate([], ['a' => range(1, 8), 'b' => range(1, 8)]);

        $this->addToAssertionCount(1);
    }

    /**
     * @param array<array-key, mixed> $options
     * @param array<array-key, mixed> $matrix
     */
    #[DataProvider('invalidProvider')]
    public function testRefusesInvalidOptionsAndMatrices(array $options, array $matrix, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        MatrixFormVariantProvider::validate($options, $matrix);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, array<array-key, mixed>, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'option without name' => [[true], [], 'The options must be keyed by their name, "0" given.'];
        yield 'matrix without name' => [[], [[true, false]], 'The matrix must be keyed by option names, "0" given.'];
        yield 'not a list' => [[], ['with_siren' => true], 'The option "with_siren" of the matrix must be a non-empty list of values, "bool" given.'];
        yield 'empty' => [[], ['with_siren' => []], 'The option "with_siren" of the matrix must be a non-empty list of values, an empty array given.'];
        yield 'keyed' => [[], ['with_siren' => ['yes' => true]], 'The option "with_siren" of the matrix must be a non-empty list of values, an array with keys given.'];
        yield 'both' => [['with_siren' => true], ['with_siren' => [true, false]], 'The option "with_siren" is both in the options and in the matrix.'];
        yield 'too many' => [[], ['a' => range(1, 8), 'b' => range(1, 9)], 'The matrix gives 72 builds, more than 64'];
    }
}
