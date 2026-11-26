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
use Silarhi\AppJsBundle\Attribute\ConstantsToJs;
use Silarhi\AppJsBundle\Constants\ConstantsCollector;
use Silarhi\AppJsBundle\Finder\MarkedClassFinder;

use function sprintf;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Gives app-js:dump-constants the classes marked #[ConstantsToJs], found two ways:
 *
 * - through autoconfiguration, which tags them: every class of a service resource is registered (the default App\
 *   resource loads src/), unless an exclude path leaves it out;
 * - in the directories of the "constants.paths" option, scanned here: for the classes no resource loads (an excluded
 *   src/Entity, for instance), and the enums on Symfony 6.4 to 7.2, which registers none.
 *
 * @internal
 */
final class ConstantsToJsPass implements CompilerPassInterface
{
    public const TAG = 'silarhi_app_js.constants';
    public const DUMPER = 'silarhi_app_js.constants_dumper';
    public const PATHS = 'silarhi_app_js.constants.paths';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::DUMPER)) {
            return;
        }

        $collector = new ConstantsCollector();
        foreach ($this->findTaggedClasses($container) as [$class, $name]) {
            $collector->add($class, $name);
        }

        foreach ($this->findScannedClasses($container) as [$class, $attribute]) {
            $collector->add($class, $attribute->name);
        }

        $container->getDefinition(self::DUMPER)->replaceArgument('$classes', $collector->getClasses());
    }

    /**
     * @return list<array{ReflectionClass<object>, string|null}> each class with its module name, when the tag gives one
     */
    private function findTaggedClasses(ContainerBuilder $container): array
    {
        $classes = [];
        // Not findTaggedServiceIds(): Symfony autoconfigures every enum as "container.excluded", which it skips
        foreach ($container->getDefinitions() as $id => $definition) {
            $tags = $definition->getTag(self::TAG);
            if ([] === $tags) {
                continue;
            }

            $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
            $reflection = is_string($class) ? $container->getReflectionClass($class, false) : null;
            if (!$reflection instanceof ReflectionClass) {
                throw new InvalidArgumentException(sprintf('#[ConstantsToJs] marks classes only, "%s" is not one.', is_string($class) ? $class : $id));
            }

            $attributes = $tags[0] ?? [];
            $classes[] = [$reflection, is_array($attributes) && is_string($attributes['name'] ?? null) ? $attributes['name'] : null];
        }

        return $classes;
    }

    /**
     * @return list<array{ReflectionClass<object>, ConstantsToJs}>
     */
    private function findScannedClasses(ContainerBuilder $container): array
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

        return MarkedClassFinder::find($paths, ConstantsToJs::class, static fn (string $class): ?ReflectionClass => $container->getReflectionClass($class, false));
    }
}
