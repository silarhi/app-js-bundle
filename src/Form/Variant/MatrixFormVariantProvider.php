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

namespace Silarhi\AppJsBundle\Form\Variant;

use function array_key_exists;
use function count;

use InvalidArgumentException;

use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;

use Override;
use ReflectionClass;

use function sprintf;

use UnitEnum;

/**
 * The options and the matrix of #[FormToJs]: one build for each combination of the values of the matrix, with the
 * options, around each variant of a provider when the attribute names one.
 *
 * @internal
 */
final readonly class MatrixFormVariantProvider implements FormVariantProviderInterface
{
    /** The most builds a matrix gives, so that a mistake does not build a form thousands of times. */
    public const MAX_COMBINATIONS = 64;

    /**
     * @param array<string, mixed>       $options
     * @param array<string, list<mixed>> $matrix
     */
    public function __construct(
        private array $options = [],
        private array $matrix = [],
        private ?FormVariantProviderInterface $variants = null,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     * @param array<array-key, mixed> $matrix
     *
     * @throws InvalidArgumentException when an option has no name, an entry of the matrix is no list of values, an
     *                                  option is both fixed and in the matrix, or the matrix has too many combinations
     */
    public static function validate(array $options, array $matrix): void
    {
        foreach (array_keys($options) as $option) {
            if (!is_string($option)) {
                throw new InvalidArgumentException(sprintf('The options must be keyed by their name, "%s" given.', $option));
            }
        }

        $combinations = 1;
        foreach ($matrix as $option => $values) {
            if (!is_string($option)) {
                throw new InvalidArgumentException(sprintf('The matrix must be keyed by option names, "%s" given.', $option));
            }

            if (!is_array($values) || [] === $values || !array_is_list($values)) {
                throw new InvalidArgumentException(sprintf('The option "%s" of the matrix must be a non-empty list of values, %s given.', $option, is_array($values) ? ([] === $values ? 'an empty array' : 'an array with keys') : '"' . get_debug_type($values) . '"'));
            }

            if (array_key_exists($option, $options)) {
                throw new InvalidArgumentException(sprintf('The option "%s" is both in the options and in the matrix.', $option));
            }

            $combinations *= count($values);
        }

        if ($combinations > self::MAX_COMBINATIONS) {
            throw new InvalidArgumentException(sprintf('The matrix gives %d builds, more than %d: give the combinations that matter with a FormVariantProviderInterface.', $combinations, self::MAX_COMBINATIONS));
        }
    }

    #[Override]
    public function getVariants(): iterable
    {
        foreach ($this->getCombinations() as $combinationName => $combination) {
            if (!$this->variants instanceof FormVariantProviderInterface) {
                yield $combinationName ?? 'default' => new FormVariant([...$this->options, ...$combination]);

                continue;
            }

            // The options of a variant win over the options and the matrix of the attribute
            foreach ($this->variants->getVariants() as $variantName => $variant) {
                $name = null === $combinationName ? (string) $variantName : $variantName . ', ' . $combinationName;

                yield $name => new FormVariant([...$this->options, ...$combination, ...$variant->options], $variant->data);
            }
        }
    }

    /**
     * Each combination of the values of the matrix, named after its values: one unnamed and empty without matrix.
     *
     * @return iterable<string|null, array<string, mixed>>
     */
    private function getCombinations(): iterable
    {
        $combinations = [[null, []]];
        foreach ($this->matrix as $option => $values) {
            $next = [];
            foreach ($combinations as [$name, $combination]) {
                foreach ($values as $value) {
                    $label = $option . ': ' . $this->describe($value);
                    $next[] = [null === $name ? $label : $name . ', ' . $label, [...$combination, $option => $value]];
                }
            }

            $combinations = $next;
        }

        foreach ($combinations as [$name, $combination]) {
            yield $name => $combination;
        }
    }

    private function describe(mixed $value): string
    {
        return match (true) {
            null === $value => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_string($value) => "'" . $value . "'",
            $value instanceof UnitEnum => (new ReflectionClass($value))->getShortName() . '::' . $value->name,
            is_scalar($value) => (string) $value,
            default => get_debug_type($value),
        };
    }
}
