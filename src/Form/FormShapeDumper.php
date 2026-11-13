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

namespace Silarhi\AppJsBundle\Form;

use Closure;

use function is_array;
use function is_object;
use function is_string;

use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use Silarhi\AppJsBundle\Form\Shape\FormShapeBuilder;
use Silarhi\AppJsBundle\Form\Shape\FormShapeRenderer;
use Silarhi\AppJsBundle\Form\Shape\ShapeNode;
use Silarhi\AppJsBundle\Form\Variant\FormVariant;
use Silarhi\AppJsBundle\Form\Variant\FormVariantProviderInterface;
use Silarhi\AppJsBundle\Writer\Format;

use function sprintf;

use Symfony\Component\Form\Button;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Throwable;

/**
 * Builds every variant of the form types marked #[FormToJs] and dumps the union of their shapes.
 *
 * @internal
 */
final readonly class FormShapeDumper
{
    /**
     * @param ContainerInterface                                                                $variantProviders the FormVariantProviderInterface of each form type that has variants
     * @param array<class-string<FormTypeInterface<mixed>>, array{name: string, dynamic: bool}> $forms            the form types marked #[FormToJs]
     */
    public function __construct(
        private FormFactoryInterface $formFactory,
        private FormRegistryInterface $formRegistry,
        private ContainerInterface $variantProviders,
        private array $forms,
        private Format $format = Format::TypeScript,
        private FormShapeBuilder $shapeBuilder = new FormShapeBuilder(),
        private FormShapeRenderer $shapeRenderer = new FormShapeRenderer(),
    ) {
    }

    /** The extension of the dumped files: ts, or d.ts for JavaScript (a shape is a type only, read through JSDoc). */
    public function getExtension(): string
    {
        return Format::JavaScript === $this->format ? 'd.ts' : 'ts';
    }

    public function dump(): FormShapeDump
    {
        $shapes = [];
        $warnings = [];
        $notices = [];
        foreach ($this->forms as $formType => ['name' => $name, 'dynamic' => $dynamic]) {
            $shape = null;
            $hasData = false;
            $listeners = [];
            foreach ($this->getVariants($formType) as $variantName => $variant) {
                $options = $this->getBuildOptions($formType, $variant->options);
                // Without data, as the page of a new entity: with its empty data
                $data = $variant->data ?? $this->getEmptyData($formType, (string) $variantName, $options, $notices);
                $form = $this->createForm($formType, (string) $variantName, $options, $data);

                $node = $this->shapeBuilder->build($form->createView(), $form);
                $shape = $shape instanceof ShapeNode ? $this->mergeShape($shape, $node, $formType) : $node;
                $hasData = $hasData || null !== $form->getConfig()->getData();
                $listeners += $this->findDataListeners($form, '');
            }

            if (!$shape instanceof ShapeNode) {
                throw new LogicException(sprintf('The form type "%s" has no variant to build.', $formType));
            }

            if ($dynamic && !$hasData && [] !== $listeners) {
                $warnings[] = sprintf(
                    '%s adds PRE_SET_DATA listeners (%s): the fields they add are missing. Give a variant with data, or set #[FormToJs(dynamic: false)] if they add none.',
                    $formType,
                    implode(', ', array_map(static fn (string $path, string $class): string => sprintf('"%s" from %s', $path, $class), array_keys($listeners), $listeners)),
                );
            }

            $shapes[$formType] = ['name' => $name, 'shape' => $shape];
        }

        // Once every shape is known: an embedded form type that is dumped too becomes a reference to its interface
        $files = [];
        foreach ($shapes as $formType => ['name' => $name, 'shape' => $shape]) {
            $files[$name . '.' . $this->getExtension()] = $this->shapeRenderer->render($name, $formType, $shape, $shapes);
        }

        ksort($files);

        return new FormShapeDump($files, $warnings, $notices);
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $formType
     *
     * @return iterable<array-key, FormVariant>
     */
    private function getVariants(string $formType): iterable
    {
        if (!$this->variantProviders->has($formType)) {
            return [new FormVariant()];
        }

        $provider = $this->variantProviders->get($formType);
        if (!$provider instanceof FormVariantProviderInterface) {
            throw new LogicException(sprintf('The variants of "%s" must come from a %s.', $formType, FormVariantProviderInterface::class));
        }

        return $provider->getVariants();
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $formType
     * @param array<string, mixed>                   $options
     *
     * @return array<string, mixed> the options of a variant, without CSRF token: it is no field of the shape, and needs
     *                              no session
     */
    private function getBuildOptions(string $formType, array $options): array
    {
        if ($this->formRegistry->getType($formType)->getOptionsResolver()->isDefined('csrf_protection')) {
            $options += ['csrf_protection' => false];
        }

        return $options;
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $formType
     * @param array<string, mixed>                   $options
     *
     * @return FormInterface<mixed>
     */
    private function createForm(string $formType, string $variantName, array $options, mixed $data): FormInterface
    {
        try {
            return $this->formFactory->create($formType, $data, $options);
        } catch (Throwable $exception) {
            throw new LogicException(sprintf('Unable to build the form "%s" (variant "%s"): %s', $formType, $variantName, $exception->getMessage()), 0, $exception);
        }
    }

    /**
     * The empty data of a form built without data, computed as Symfony does when the form is submitted empty: a new
     * instance of its data_class by default, or what its own empty_data gives. Its options are resolved and its
     * empty_data called on a bare form of the same data_class, so neither buildForm() nor a PRE_SET_DATA listener runs
     * without data. Only an object counts: the [] or '' of a type without data_class, or the null of a form that is not
     * required, is no data.
     *
     * @param class-string<FormTypeInterface<mixed>> $formType
     * @param array<string, mixed>                   $options
     * @param list<string>                           $notices
     */
    private function getEmptyData(string $formType, string $variantName, array $options, array &$notices): ?object
    {
        try {
            $resolved = $this->formRegistry->getType($formType)->getOptionsResolver()->resolve($options);
            $emptyData = $resolved['empty_data'] ?? null;
            if ($emptyData instanceof Closure) {
                $emptyData = $emptyData($this->formFactory->create(FormType::class, null, [
                    'data_class' => $resolved['data_class'] ?? null,
                    'required' => $resolved['required'] ?? true,
                    'compound' => $resolved['compound'] ?? true,
                    'auto_initialize' => false,
                ]), null);
            }
        } catch (Throwable $exception) {
            $notices[] = sprintf('%s (variant "%s") is built without data: its empty data failed (%s).', $formType, $variantName, $exception->getMessage());

            return null;
        }

        return is_object($emptyData) ? $emptyData : null;
    }

    private function mergeShape(ShapeNode $shape, ShapeNode $node, string $formType): ShapeNode
    {
        try {
            return $shape->merge($node);
        } catch (LogicException $exception) {
            throw new LogicException(sprintf('Unable to merge the variants of "%s": %s', $formType, $exception->getMessage()), 0, $exception);
        }
    }

    /**
     * The PRE_SET_DATA listeners of the application (Symfony's own add no field) in a form and its children. A button
     * has no listener: it supports no event dispatching.
     *
     * @param FormInterface<mixed> $form
     *
     * @return array<string, string> the class of a listener, by the path of the form it listens on
     */
    private function findDataListeners(FormInterface $form, string $path): array
    {
        if ($form instanceof Button) {
            return [];
        }

        $listeners = [];
        foreach ($form->getConfig()->getEventDispatcher()->getListeners(FormEvents::PRE_SET_DATA) as $listener) {
            $class = $this->getListenerClass($listener);
            if (null !== $class && !str_starts_with($class, 'Symfony\\')) {
                $listeners['' === $path ? '(root)' : $path] = $class;
            }
        }

        $prototype = $form->getConfig()->getAttribute('prototype');
        if ($prototype instanceof FormInterface) {
            $listeners += $this->findDataListeners($prototype, ('' === $path ? '' : $path . '.') . '{index}');
        }

        foreach ($form as $name => $child) {
            $listeners += $this->findDataListeners($child, ('' === $path ? '' : $path . '.') . $name);
        }

        return $listeners;
    }

    private function getListenerClass(mixed $listener): ?string
    {
        if ($listener instanceof Closure) {
            return (new ReflectionFunction($listener))->getClosureScopeClass()?->getName();
        }

        if (is_array($listener) && isset($listener[0])) {
            return is_object($listener[0]) ? $listener[0]::class : (is_string($listener[0]) ? $listener[0] : null);
        }

        return is_object($listener) ? $listener::class : null;
    }
}
