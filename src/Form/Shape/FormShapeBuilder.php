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
        if (true !== ($view->vars['compound'] ?? false) || $expandedChoice) {
            return ShapeNode::field();
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
     * @param FormInterface<mixed>|null $form
     *
     * @return FormInterface<mixed>|null the child named $name, null without a form or such a child
     */
    private function getChild(?FormInterface $form, string $name): ?FormInterface
    {
        return $form instanceof FormInterface && $form->has($name) ? $form->get($name) : null;
    }
}
