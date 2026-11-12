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

namespace Silarhi\AppJsBundle\Enum;

use function dirname;

use ReflectionEnum;
use Silarhi\AppJsBundle\Attribute\EnumToJs;
use Symfony\Component\Finder\Finder;
use UnitEnum;

/**
 * The enums marked #[EnumToJs] in the configured directories, read with their PSR-4 namespace.
 *
 * @internal
 */
final readonly class EnumLocator
{
    /**
     * @param array<string, string> $paths namespace prefix by directory
     */
    public function __construct(private array $paths)
    {
    }

    /**
     * @return array<string, class-string<UnitEnum>> the enums by the path of their module: relative to their directory, without extension
     */
    public function findEnums(): array
    {
        $enums = [];
        foreach ($this->paths as $directory => $namespace) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = (new Finder())->files()->in($directory)->name('*.php')->sortByName();
            foreach ($files as $file) {
                $relativePath = substr($file->getRelativePathname(), 0, -4);
                $class = rtrim($namespace, '\\') . '\\' . str_replace('/', '\\', $relativePath);

                // Classes living next to the enums (constant holders, interfaces) are skipped
                if (!enum_exists($class)) {
                    continue;
                }

                $attribute = (new ReflectionEnum($class))->getAttributes(EnumToJs::class)[0] ?? null;
                if (null === $attribute) {
                    continue;
                }

                $name = $attribute->newInstance()->name;
                if (null !== $name) {
                    $relativePath = ('.' === dirname($relativePath) ? '' : dirname($relativePath) . '/') . $name;
                }

                $enums[$relativePath] = $class;
            }
        }

        return $enums;
    }
}
