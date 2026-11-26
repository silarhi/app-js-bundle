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

namespace Silarhi\AppJsBundle\Finder;

use Closure;

use function is_string;

use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Finds the classes of directories marked with an attribute: the scan behind the "paths" options of the bundle, and
 * behind the fromDirectories() constructors of the dumpers without it.
 *
 * @internal
 */
final class MarkedClassFinder
{
    /**
     * @template T of object
     *
     * @param array<array-key, mixed>                                $paths     the directories, with their namespace prefix; the other entries are skipped
     * @param class-string<T>                                        $attribute
     * @param (Closure(string): (ReflectionClass<object>|null))|null $reflector reflects a class, null when it does not exist (autoloads it by default)
     *
     * @return list<array{ReflectionClass<object>, T}> each marked class with its attribute, by path then by file name
     */
    public static function find(array $paths, string $attribute, ?Closure $reflector = null): array
    {
        $reflector ??= static fn (string $class): ?ReflectionClass => class_exists($class) ? new ReflectionClass($class) : null;

        $classes = [];
        foreach ($paths as $directory => $namespace) {
            if (!is_string($directory) || !is_string($namespace) || !is_dir($directory)) {
                continue;
            }

            foreach ((new Finder())->files()->in($directory)->name('*.php')->sortByName() as $file) {
                $reflection = $reflector(rtrim($namespace, '\\') . '\\' . str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4)));
                $attributes = $reflection?->getAttributes($attribute) ?? [];
                if (null === $reflection || [] === $attributes) {
                    continue;
                }

                $classes[] = [$reflection, $attributes[0]->newInstance()];
            }
        }

        return $classes;
    }
}
