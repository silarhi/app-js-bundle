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

use InvalidArgumentException;

use function is_array;

use Override;

use function sprintf;

/**
 * The variants given as an array in #[FormToJs].
 *
 * @internal
 */
final readonly class StaticFormVariantProvider implements FormVariantProviderInterface
{
    /**
     * @param array<array-key, array<string, mixed>> $variants
     */
    public function __construct(private array $variants)
    {
    }

    /**
     * @param array<array-key, mixed> $variants
     *
     * @throws InvalidArgumentException when a variant is not an array with "options" and "data" keys only
     */
    public static function validate(array $variants): void
    {
        foreach ($variants as $name => $variant) {
            if (!is_array($variant)) {
                throw new InvalidArgumentException(sprintf('The variant "%s" must be an array, "%s" given.', $name, get_debug_type($variant)));
            }

            $unknownKeys = array_diff(array_keys($variant), ['options', 'data']);
            if ([] !== $unknownKeys) {
                throw new InvalidArgumentException(sprintf('The variant "%s" has unknown keys "%s": only "options" and "data" are allowed.', $name, implode('", "', $unknownKeys)));
            }

            if (isset($variant['options']) && !is_array($variant['options'])) {
                throw new InvalidArgumentException(sprintf('The options of the variant "%s" must be an array, "%s" given.', $name, get_debug_type($variant['options'])));
            }
        }
    }

    #[Override]
    public function getVariants(): iterable
    {
        foreach ($this->variants as $name => $variant) {
            /** @var array<string, mixed> $options */
            $options = $variant['options'] ?? [];

            yield $name => new FormVariant($options, $variant['data'] ?? null);
        }
    }
}
