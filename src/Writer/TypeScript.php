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

/**
 * @internal
 */
final class TypeScript
{
    /**
     * A string literal as Biome formats it: single-quoted, unless the value holds more single quotes than double ones.
     */
    public static function quote(string $value): string
    {
        $quote = substr_count($value, "'") > substr_count($value, '"') ? '"' : "'";

        return $quote . str_replace(['\\', $quote, "\n"], ['\\\\', '\\' . $quote, '\\n'], $value) . $quote;
    }

    /** A property key: bare when it is an identifier, quoted otherwise. */
    public static function key(string $name): string
    {
        return 1 === preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $name) ? $name : self::quote($name);
    }
}
