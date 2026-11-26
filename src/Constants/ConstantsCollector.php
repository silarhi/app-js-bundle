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

namespace Silarhi\AppJsBundle\Constants;

use InvalidArgumentException;
use ReflectionClass;

use function sprintf;

/**
 * Indexes the classes marked #[ConstantsToJs] by module name: for ConstantsToJsPass, and for
 * ConstantsDumper::fromDirectories() without the bundle.
 *
 * @internal
 */
final class ConstantsCollector
{
    /** @var array<string, class-string> */
    private array $classes = [];

    /**
     * @param ReflectionClass<object> $class
     * @param string|null             $name  its module name, its short name by default
     *
     * @throws InvalidArgumentException when another class has the same name
     */
    public function add(ReflectionClass $class, ?string $name = null): void
    {
        $name ??= $class->getShortName();
        if (isset($this->classes[$name]) && $this->classes[$name] !== $class->getName()) {
            throw new InvalidArgumentException(sprintf('The classes "%s" and "%s" both dump their constants as "%s": give one a name.', $this->classes[$name], $class->getName(), $name));
        }

        $this->classes[$name] = $class->getName();
    }

    /**
     * @return array<string, class-string> the classes, by module name
     */
    public function getClasses(): array
    {
        $classes = $this->classes;
        ksort($classes);

        return $classes;
    }
}
