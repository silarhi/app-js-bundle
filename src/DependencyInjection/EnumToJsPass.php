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
use Silarhi\AppJsBundle\Enum\EnumCollector;
use Silarhi\AppJsBundle\Finder\MarkedClassFinder;

use function sprintf;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

        $collector = new EnumCollector();
        foreach ($this->findTaggedEnums($container) as [$enum, $name]) {
            $collector->add($enum, $name);
        }

        foreach ($this->findScannedEnums($container) as [$enum, $attribute]) {
            if ($enum->isEnum()) {
                $collector->add($enum, $attribute->name);
            }
        }

        $container->getDefinition(self::DUMPER)
            ->replaceArgument('$enums', $collector->getEnums())
            ->replaceArgument('$methods', $collector->getMethods());
    }

    /**
     * @return list<array{ReflectionClass<object>, string|null}> each enum with its module name, when the tag gives one
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
            $reflection = is_string($class) && enum_exists($class) ? $container->getReflectionClass($class, false) : null;
            if (!$reflection instanceof ReflectionClass) {
                throw new InvalidArgumentException(sprintf('#[EnumToJs] marks enums only, "%s" is not one.', is_string($class) ? $class : $id));
            }

            $attributes = $tags[0] ?? [];
            $enums[] = [$reflection, is_array($attributes) && is_string($attributes['name'] ?? null) ? $attributes['name'] : null];
        }

        return $enums;
    }

    /**
     * @return list<array{ReflectionClass<object>, EnumToJs}>
     */
    private function findScannedEnums(ContainerBuilder $container): array
    {
        $paths = $container->hasParameter(self::PATHS) ? $container->getParameterBag()->resolveValue($container->getParameter(self::PATHS)) : [];
        if (!is_array($paths)) {
            return [];
        }

        foreach (array_keys($paths) as $directory) {
            // A new file in the directory rebuilds the container
            if (is_string($directory) && is_dir($directory)) {
                $container->addResource(new DirectoryResource($directory, '/\.php$/'));
            }
        }

        return MarkedClassFinder::find($paths, EnumToJs::class, static fn (string $class): ?ReflectionClass => $container->getReflectionClass($class, false));
    }
}
