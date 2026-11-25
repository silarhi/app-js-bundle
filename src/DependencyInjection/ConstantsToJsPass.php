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

use function sprintf;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

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

        $classes = [];
        foreach ([...$this->findTaggedClasses($container), ...$this->findScannedClasses($container)] as [$class, $name]) {
            if (isset($classes[$name]) && $classes[$name] !== $class) {
                throw new InvalidArgumentException(sprintf('The classes "%s" and "%s" both dump their constants as "%s": give one a name.', $classes[$name], $class, $name));
            }

            $classes[$name] = $class;
        }

        ksort($classes);

        $container->getDefinition(self::DUMPER)->replaceArgument('$classes', $classes);
    }

    /**
     * @return list<array{class-string, string}> each class with its module name
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
            $name = is_array($attributes) && is_string($attributes['name'] ?? null) ? $attributes['name'] : null;
            $classes[] = [$reflection->getName(), $name ?? $reflection->getShortName()];
        }

        return $classes;
    }

    /**
     * @return list<array{class-string, string}> each class with its module name
     */
    private function findScannedClasses(ContainerBuilder $container): array
    {
        $paths = $container->hasParameter(self::PATHS) ? $container->getParameterBag()->resolveValue($container->getParameter(self::PATHS)) : [];
        if (!is_array($paths)) {
            return [];
        }

        $classes = [];
        foreach ($paths as $directory => $namespace) {
            if (!is_string($directory) || !is_string($namespace) || !is_dir($directory)) {
                continue;
            }

            // A new file in the directory rebuilds the container
            $container->addResource(new DirectoryResource($directory, '/\.php$/'));
            foreach ((new Finder())->files()->in($directory)->name('*.php')->sortByName() as $file) {
                $class = rtrim($namespace, '\\') . '\\' . str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
                $reflection = $container->getReflectionClass($class, false);
                $attribute = $reflection?->getAttributes(ConstantsToJs::class)[0] ?? null;
                if (!$reflection instanceof ReflectionClass || null === $attribute) {
                    continue;
                }

                $classes[] = [$reflection->getName(), $attribute->newInstance()->name ?? $reflection->getShortName()];
            }
        }

        return $classes;
    }
}
