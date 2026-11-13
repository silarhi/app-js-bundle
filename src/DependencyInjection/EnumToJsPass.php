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

namespace Silarhi\AppJsBundle\DependencyInjection;

use InvalidArgumentException;

use function is_array;
use function is_string;

use Override;
use ReflectionClass;
use Silarhi\AppJsBundle\Attribute\EnumToJs;

use function sprintf;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use UnitEnum;

/**
 * Gives app-js:dump-enums the enums marked #[EnumToJs], found two ways:
 *
 * - through autoconfiguration, which tags them: Symfony 7.3+ registers every class of a service resource, enums
 *   included (the default App\ resource loads src/Enum), unless an exclude path leaves them out;
 * - in the directories of the "enums.paths" option, scanned here: Symfony 6.4 to 7.2 registers no enum.
 *
 * @internal
 */
final class EnumToJsPass implements CompilerPassInterface
{
    public const TAG = 'silarhi_app_js.enum';
    public const DUMPER = 'silarhi_app_js.enum_dumper';
    public const PATHS = 'silarhi_app_js.enums.paths';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::DUMPER)) {
            return;
        }

        $enums = [];
        foreach ([...$this->findTaggedEnums($container), ...$this->findScannedEnums($container)] as [$class, $name]) {
            if (isset($enums[$name]) && $enums[$name] !== $class) {
                throw new InvalidArgumentException(sprintf('The enums "%s" and "%s" are both dumped as "%s": give one a name.', $enums[$name], $class, $name));
            }

            $enums[$name] = $class;
        }

        ksort($enums);

        $container->getDefinition(self::DUMPER)->replaceArgument('$enums', $enums);
    }

    /**
     * @return list<array{class-string<UnitEnum>, string}> each enum with its module name
     */
    private function findTaggedEnums(ContainerBuilder $container): array
    {
        $enums = [];
        // Not findTaggedServiceIds(): Symfony autoconfigures every enum as "container.excluded", which it skips
        foreach ($container->getDefinitions() as $id => $definition) {
            $tags = $definition->getTag(self::TAG);
            if ([] === $tags) {
                continue;
            }

            $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
            if (!is_string($class) || !enum_exists($class)) {
                throw new InvalidArgumentException(sprintf('#[EnumToJs] marks enums only, "%s" is not one.', is_string($class) ? $class : $id));
            }

            $attributes = $tags[0] ?? [];
            $name = is_array($attributes) && is_string($attributes['name'] ?? null) ? $attributes['name'] : null;
            $enums[] = [$class, $name ?? (new ReflectionClass($class))->getShortName()];
        }

        return $enums;
    }

    /**
     * @return list<array{class-string<UnitEnum>, string}> each enum with its module name
     */
    private function findScannedEnums(ContainerBuilder $container): array
    {
        $paths = $container->hasParameter(self::PATHS) ? $container->getParameterBag()->resolveValue($container->getParameter(self::PATHS)) : [];
        if (!is_array($paths)) {
            return [];
        }

        $enums = [];
        foreach ($paths as $directory => $namespace) {
            if (!is_string($directory) || !is_string($namespace) || !is_dir($directory)) {
                continue;
            }

            // A new file in the directory rebuilds the container
            $container->addResource(new DirectoryResource($directory, '/\.php$/'));
            foreach ((new Finder())->files()->in($directory)->name('*.php')->sortByName() as $file) {
                $class = rtrim($namespace, '\\') . '\\' . str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
                $reflection = $container->getReflectionClass($class, false);
                $attribute = $reflection?->getAttributes(EnumToJs::class)[0] ?? null;
                if (!$reflection instanceof ReflectionClass || null === $attribute || !$reflection->isEnum()) {
                    continue;
                }

                /** @var class-string<UnitEnum> $enum */
                $enum = $reflection->getName();
                $enums[] = [$enum, $attribute->newInstance()->name ?? $reflection->getShortName()];
            }
        }

        return $enums;
    }
}
