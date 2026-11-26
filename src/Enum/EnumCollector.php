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

use InvalidArgumentException;
use ReflectionClass;
use Silarhi\AppJsBundle\Attribute\EnumToJs;

use function sprintf;

use UnitEnum;

/**
 * Indexes the enums marked #[EnumToJs] by module name, and checks the methods they dump: for EnumToJsPass, and for
 * EnumDumper::fromDirectories() without the bundle.
 *
 * @internal
 */
final class EnumCollector
{
    /** @var array<string, class-string<UnitEnum>> */
    private array $enums = [];

    /** @var array<string, list<string>> */
    private array $methods = [];

    /**
     * @param ReflectionClass<object> $enum an enum
     * @param string|null             $name its module name, its short name by default
     *
     * @throws InvalidArgumentException when another enum has the same name, or a dumped method cannot be called
     */
    public function add(ReflectionClass $enum, ?string $name = null): void
    {
        /** @var class-string<UnitEnum> $class */
        $class = $enum->getName();
        $name ??= $enum->getShortName();
        if (isset($this->enums[$name]) && $this->enums[$name] !== $class) {
            throw new InvalidArgumentException(sprintf('The enums "%s" and "%s" are both dumped as "%s": give one a name.', $this->enums[$name], $class, $name));
        }

        $this->enums[$name] = $class;
        $methods = $this->getDumpedMethods($enum);
        if ([] !== $methods) {
            $this->methods[$name] = $methods;
        }
    }

    /**
     * @return array<string, class-string<UnitEnum>> the enums, by module name
     */
    public function getEnums(): array
    {
        $enums = $this->enums;
        ksort($enums);

        return $enums;
    }

    /**
     * @return array<string, list<string>> the methods to dump the results of, by module name
     */
    public function getMethods(): array
    {
        $methods = $this->methods;
        ksort($methods);

        return $methods;
    }

    /**
     * The methods of #[EnumToJs(methods: [...])], checked: each one is called on every case by the dump.
     *
     * @param ReflectionClass<object> $enum
     *
     * @return list<string>
     */
    private function getDumpedMethods(ReflectionClass $enum): array
    {
        $attribute = $enum->getAttributes(EnumToJs::class)[0] ?? null;
        if (null === $attribute) {
            return [];
        }

        $methods = $attribute->newInstance()->methods;
        foreach ($methods as $method) {
            if (!$enum->hasMethod($method)) {
                throw new InvalidArgumentException(sprintf('The #[EnumToJs] method "%s" of "%s" does not exist.', $method, $enum->getName()));
            }

            $reflectionMethod = $enum->getMethod($method);
            if (!$reflectionMethod->isPublic() || $reflectionMethod->isStatic() || $reflectionMethod->getNumberOfRequiredParameters() > 0) {
                throw new InvalidArgumentException(sprintf('The #[EnumToJs] method "%s::%s()" must be public, not static, and take no required parameter.', $enum->getName(), $method));
            }
        }

        return $methods;
    }
}
