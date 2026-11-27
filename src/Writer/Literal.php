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

namespace Silarhi\AppJsBundle\Writer;

use BackedEnum;
use InvalidArgumentException;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

use const JSON_THROW_ON_ERROR;

use function sprintf;
use function strlen;

use Symfony\Component\Translation\TranslatableMessage;
use UnitEnum;

/**
 * A PHP value as a JavaScript literal, formatted as Biome formats it: a scalar, null, an enum case (its value, or its
 * name for a unit enum), a translatable message (its key), or an array of those, frozen.
 *
 * @internal
 */
final class Literal
{
    /** Biome's line width: a longer array is written one item per line, as Biome would reformat it */
    private const LINE_WIDTH = 120;

    /**
     * @param int $column where the literal starts on its line, to keep an array within the line width
     * @param int $depth  the indentation level of the line the literal starts on
     *
     * @throws InvalidArgumentException when the value has no JavaScript literal
     */
    public static function render(mixed $value, int $column = 0, int $depth = 0): string
    {
        return match (true) {
            null === $value => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) => (string) $value,
            is_float($value) => self::renderFloat($value),
            is_string($value) => TypeScript::quote($value),
            $value instanceof BackedEnum => self::render($value->value, $column, $depth),
            $value instanceof UnitEnum => TypeScript::quote($value->name),
            // The page translates it: its parameters are the page's business
            $value instanceof TranslatableMessage => TypeScript::quote($value->getMessage()),
            is_array($value) => self::renderArray($value, $column, $depth),
            default => throw new InvalidArgumentException(sprintf('a %s has no JavaScript literal.', get_debug_type($value))),
        };
    }

    private static function renderFloat(float $value): string
    {
        // JSON has no literal for them, JavaScript has
        if (is_nan($value)) {
            return 'NaN';
        }

        if (is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }

        // The shortest notation that reads back the same float, as JavaScript writes it
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * A list as an array, a map as an object, frozen. On one line when it fits, one item per line otherwise; an object
     * always one property per line, as Biome keeps them.
     *
     * @param array<array-key, mixed> $value
     */
    private static function renderArray(array $value, int $column, int $depth): string
    {
        $indent = str_repeat('    ', $depth + 1);
        if (array_is_list($value)) {
            $items = array_map(static fn (mixed $item): string => self::render($item, strlen($indent), $depth + 1), $value);
            $inline = sprintf('Object.freeze([%s])', implode(', ', $items));
            if ([] === $items || (!str_contains($inline, "\n") && $column + strlen($inline) <= self::LINE_WIDTH)) {
                return $inline;
            }

            return sprintf("Object.freeze([\n%s,\n%s])", $indent . implode(",\n" . $indent, $items), str_repeat('    ', $depth));
        }

        // Never empty here: an empty array is a list
        return sprintf("Object.freeze({\n%s\n%s})", implode("\n", self::renderProperties($value, $depth + 1)), str_repeat('    ', $depth));
    }

    /**
     * The properties of an object, one per line.
     *
     * @param array<array-key, mixed> $value
     *
     * @return list<string>
     */
    private static function renderProperties(array $value, int $depth): array
    {
        $indent = str_repeat('    ', $depth);
        $properties = [];
        foreach ($value as $key => $item) {
            $key = TypeScript::key((string) $key);
            $properties[] = sprintf('%s%s: %s,', $indent, $key, self::render($item, strlen($indent . $key) + 2, $depth));
        }

        return $properties;
    }
}
