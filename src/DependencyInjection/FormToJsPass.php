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

use function is_string;

use Override;
use ReflectionClass;
use Silarhi\AppJsBundle\Attribute\FormToJs;
use Silarhi\AppJsBundle\Form\Variant\FormVariantProviderInterface;
use Silarhi\AppJsBundle\Form\Variant\MatrixFormVariantProvider;

use function sprintf;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Finds the form types marked #[FormToJs] and gives app-js:dump-forms their variant providers: the service named by
 * the attribute, wrapped in a MatrixFormVariantProvider when it gives options or a matrix.
 *
 * @internal
 */
final class FormToJsPass implements CompilerPassInterface
{
    public const DUMPER = 'silarhi_app_js.form_shape_dumper';
    public const COMMAND = 'silarhi_app_js.command.dump_forms';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::DUMPER)) {
            return;
        }

        // symfony/form is installed, but FrameworkBundle's form integration is disabled
        if (!$container->has('form.factory') || !$container->has('form.registry')) {
            $container->removeDefinition(self::DUMPER);
            $container->removeDefinition(self::COMMAND);

            return;
        }

        $forms = [];
        $providers = [];
        $formTypesByName = [];
        foreach (array_keys($container->findTaggedServiceIds('form.type')) as $id) {
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id);
            if (!is_string($class)) {
                continue;
            }

            $reflection = $container->getReflectionClass($class, false);
            $attribute = $reflection?->getAttributes(FormToJs::class)[0] ?? null;
            if (!$reflection instanceof ReflectionClass || null === $attribute || !$reflection->implementsInterface(FormTypeInterface::class)) {
                continue;
            }

            $formType = $reflection->getName();
            $formToJs = $attribute->newInstance();
            $name = $formToJs->name ?? preg_replace('/Type$/', '', $reflection->getShortName()) . 'Form';
            if (isset($formTypesByName[$name])) {
                throw new InvalidArgumentException(sprintf('The form types "%s" and "%s" are both exposed as "%s": give one a name.', $formTypesByName[$name], $formType, $name));
            }

            $formTypesByName[$name] = $formType;
            $forms[$formType] = ['name' => $name, 'dynamic' => $formToJs->dynamic];
            $provider = $this->registerProvider($container, $formType, $formToJs);
            if ($provider instanceof Reference) {
                $providers[$formType] = $provider;
            }
        }

        ksort($forms);

        $container->getDefinition(self::DUMPER)
            ->replaceArgument('$variantProviders', ServiceLocatorTagPass::register($container, $providers))
            ->replaceArgument('$forms', $forms);
    }

    /**
     * The provider of the builds of a form type: none for one build with the default options, the service named by
     * the attribute alone, or a MatrixFormVariantProvider with the options and the matrix around it.
     */
    private function registerProvider(ContainerBuilder $container, string $formType, FormToJs $formToJs): ?Reference
    {
        $variants = null;
        if (null !== $formToJs->variants) {
            if (!is_subclass_of($formToJs->variants, FormVariantProviderInterface::class)) {
                throw new InvalidArgumentException(sprintf('The #[FormToJs] variants of "%s" must be the class of a %s, "%s" given.', $formType, FormVariantProviderInterface::class, $formToJs->variants));
            }

            // A provider outside the application's service resources is registered on the fly
            if (!$container->has($formToJs->variants)) {
                $container->setDefinition($formToJs->variants, (new Definition($formToJs->variants))->setAutowired(true)->setAutoconfigured(true));
            }

            $variants = new Reference($formToJs->variants);
        }

        if ([] === $formToJs->options && [] === $formToJs->matrix) {
            return $variants;
        }

        try {
            MatrixFormVariantProvider::validate($formToJs->options, $formToJs->matrix);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(sprintf('Invalid #[FormToJs] on "%s": %s', $formType, $exception->getMessage()), 0, $exception);
        }

        $id = '.silarhi_app_js.form_variants.' . ContainerBuilder::hash($formType);
        $container->setDefinition($id, new Definition(MatrixFormVariantProvider::class, [$formToJs->options, $formToJs->matrix, $variants]));

        return new Reference($id);
    }
}
