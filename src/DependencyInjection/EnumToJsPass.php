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
use ReflectionEnum;

use function sprintf;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Gives app-js:dump-enums the enums marked #[EnumToJs]: the bundle tags them through autoconfiguration, so they must
 * be loaded by a service resource (the default App\ one loads src/Enum), not excluded from it.
 *
 * @internal
 */
final class EnumToJsPass implements CompilerPassInterface
{
    public const TAG = 'silarhi_app_js.enum';
    public const DUMPER = 'silarhi_app_js.enum_dumper';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::DUMPER)) {
            return;
        }

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
            $name = is_array($attributes) && is_string($attributes['name'] ?? null)
                ? $attributes['name']
                : (new ReflectionEnum($class))->getShortName();
            if (isset($enums[$name]) && $enums[$name] !== $class) {
                throw new InvalidArgumentException(sprintf('The enums "%s" and "%s" are both dumped as "%s": give one a name.', $enums[$name], $class, $name));
            }

            $enums[$name] = $class;
        }

        ksort($enums);

        $container->getDefinition(self::DUMPER)->replaceArgument('$enums', $enums);
    }
}
