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

namespace Silarhi\AppJsBundle\Form\Shape;

use function in_array;
use function is_array;
use function is_string;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * The shape of a rendered form, read from its view as formView() of @silarhi/app-js reads the DOM. The form, when
 * given, tells which form type built each embedded form.
 *
 * @internal
 */
final class FormShapeBuilder
{
    /**
     * The element a field is rendered as and the value formView() reads from it, by the block prefix of a core form
     * type. The most specific prefix wins: a textarea is a text too, an entity a choice, a radio a checkbox.
     */
    private const FIELDS = [
        'button' => ['HTMLButtonElement', null],
        'checkbox' => ['HTMLInputElement', 'boolean'],
        // A single or a multiple select: see getField()
        'choice' => ['HTMLSelectElement', null],
        'date' => ['HTMLInputElement', 'string'],
        'datetime' => ['HTMLInputElement', 'string'],
        'file' => ['HTMLInputElement', 'string'],
        'hidden' => ['HTMLInputElement', 'string'],
        'integer' => ['HTMLInputElement', 'string'],
        'money' => ['HTMLInputElement', 'string'],
        'number' => ['HTMLInputElement', 'string'],
        'percent' => ['HTMLInputElement', 'string'],
        // The checked value of its group, if any
        'radio' => ['HTMLInputElement', 'string | undefined'],
        'text' => ['HTMLInputElement', 'string'],
        'textarea' => ['HTMLTextAreaElement', 'string'],
        'time' => ['HTMLInputElement', 'string'],
        'week' => ['HTMLInputElement', 'string'],
    ];

    /**
     * The choice types whose values are not typed: entity ids come from the database, the dump must give the same
     * files on every machine; the Intl lists are long and follow the ICU data.
     */
    private const UNTYPED_CHOICES = ['entity', 'document', 'country', 'currency', 'language', 'locale', 'timezone'];

    /** The types whose choice widgets are not typed: the years of a date follow the current one */
    private const UNTYPED_CHOICE_PARENTS = ['date', 'dateinterval', 'time', 'week'];

    /**
     * @param FormInterface<mixed>|null $form the form of the view
     */
    public function build(FormView $view, ?FormInterface $form = null): ShapeNode
    {
        $prefixes = $view->vars['block_prefixes'] ?? [];
        $prefixes = is_array($prefixes) ? $prefixes : [];

        if (in_array('collection', $prefixes, true)) {
            return ShapeNode::collection($this->buildItem($view, $form));
        }

        // An expanded choice is compound (one child per option), but formView() reads it as one field
        $expandedChoice = in_array('choice', $prefixes, true) && true === ($view->vars['expanded'] ?? false);
        if ($expandedChoice) {
            // Its element is the container of the options, its value the ones checked
            return ShapeNode::field(value: $this->isMultiple($view) ? 'string[]' : 'string', choices: $this->getChoices($view, $prefixes));
        }

        if (true !== ($view->vars['compound'] ?? false)) {
            return $this->getField($view, $prefixes);
        }

        $children = [];
        foreach ($view->children as $name => $child) {
            $children[(string) $name] = $this->build($child, $this->getChild($form, (string) $name));
        }

        return ShapeNode::form($children, $form instanceof FormInterface ? $form->getConfig()->getType()->getInnerType()::class : null);
    }

    /**
     * The item shape of a collection: its prototype and the items it rendered, merged.
     *
     * @param FormInterface<mixed>|null $collection
     */
    private function buildItem(FormView $collectionView, ?FormInterface $collection): ?ShapeNode
    {
        $item = null;
        $prototype = $collectionView->vars['prototype'] ?? null;
        if ($prototype instanceof FormView) {
            $prototypeForm = $collection?->getConfig()->getAttribute('prototype');
            $item = $this->build($prototype, $prototypeForm instanceof FormInterface ? $prototypeForm : null);
        }

        foreach ($collectionView->children as $name => $child) {
            $node = $this->build($child, $this->getChild($collection, (string) $name));
            $item = $item?->merge($node) ?? $node;
        }

        return $item;
    }

    /**
     * @param array<mixed> $prefixes the block prefixes of a field, from the form type to the field itself
     *
     * @return ShapeNode its element and value, unknown when no core type among its prefixes tells
     */
    private function getField(FormView $view, array $prefixes): ShapeNode
    {
        foreach (array_reverse($prefixes) as $prefix) {
            if (!is_string($prefix) || !isset(self::FIELDS[$prefix])) {
                continue;
            }

            [$element, $value] = self::FIELDS[$prefix];
            if ('choice' === $prefix) {
                // No option selected reads as null
                $value = $this->isMultiple($view) ? 'string[]' : 'string | null';

                return ShapeNode::field($element, $value, $this->getChoices($view, $prefixes));
            }

            return ShapeNode::field($element, $value);
        }

        return ShapeNode::field();
    }

    /**
     * The values of the options of a choice, in rendering order, the placeholder's empty value included.
     *
     * @param array<mixed> $prefixes
     *
     * @return list<string>|null null when its values are not typed
     */
    private function getChoices(FormView $view, array $prefixes): ?array
    {
        $parentPrefixes = $view->parent?->vars['block_prefixes'] ?? [];
        if ($this->hasAnyPrefix($prefixes, self::UNTYPED_CHOICES) || (is_array($parentPrefixes) && $this->hasAnyPrefix($parentPrefixes, self::UNTYPED_CHOICE_PARENTS))) {
            return null;
        }

        $choices = $view->vars['choices'] ?? [];
        $values = $this->getChoiceValues(is_array($choices) ? $choices : []);
        if ([] === $values) {
            return null;
        }

        // A placeholder option is rendered: a select reads its empty value, and so does a radio group
        if (null !== ($view->vars['placeholder'] ?? null) && !in_array('', $values, true)) {
            $values[] = '';
        }

        return $values;
    }

    /**
     * @param array<mixed> $choices the ChoiceView of a choice, grouped in ChoiceGroupView
     *
     * @return list<string>
     */
    private function getChoiceValues(array $choices): array
    {
        $values = [];
        foreach ($choices as $choice) {
            $choiceValues = [];
            if ($choice instanceof ChoiceGroupView) {
                $choiceValues = $this->getChoiceValues($choice->choices);
            } elseif ($choice instanceof ChoiceView) {
                $choiceValues = [$choice->value];
            }

            foreach ($choiceValues as $value) {
                if (!in_array($value, $values, true)) {
                    $values[] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * @param array<mixed> $prefixes
     * @param list<string> $names
     */
    private function hasAnyPrefix(array $prefixes, array $names): bool
    {
        foreach ($names as $name) {
            if (in_array($name, $prefixes, true)) {
                return true;
            }
        }

        return false;
    }

    private function isMultiple(FormView $view): bool
    {
        return true === ($view->vars['multiple'] ?? false);
    }

    /**
     * @param FormInterface<mixed>|null $form
     *
     * @return FormInterface<mixed>|null the child named $name, null without a form or such a child
     */
    private function getChild(?FormInterface $form, string $name): ?FormInterface
    {
        return $form instanceof FormInterface && $form->has($name) ? $form->get($name) : null;
    }
}
