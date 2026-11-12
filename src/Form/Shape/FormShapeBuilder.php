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

use Symfony\Component\Form\FormView;

/**
 * The shape of a rendered form, read from its view as formView() of @silarhi/app-js reads the DOM.
 *
 * @internal
 */
final class FormShapeBuilder
{
    public function build(FormView $view): ShapeNode
    {
        $prefixes = $view->vars['block_prefixes'] ?? [];
        $prefixes = is_array($prefixes) ? $prefixes : [];

        if (in_array('collection', $prefixes, true)) {
            return ShapeNode::collection($this->buildItem($view));
        }

        // An expanded choice is compound (one child per option), but formView() reads it as one field
        $expandedChoice = in_array('choice', $prefixes, true) && true === ($view->vars['expanded'] ?? false);
        if (true !== ($view->vars['compound'] ?? false) || $expandedChoice) {
            return ShapeNode::field();
        }

        $children = [];
        foreach ($view->children as $name => $child) {
            $children[(string) $name] = $this->build($child);
        }

        return ShapeNode::form($children);
    }

    /** The item shape of a collection: its prototype and the items it rendered, merged. */
    private function buildItem(FormView $collection): ?ShapeNode
    {
        $item = null;
        $prototype = $collection->vars['prototype'] ?? null;
        if ($prototype instanceof FormView) {
            $item = $this->build($prototype);
        }

        foreach ($collection->children as $child) {
            $node = $this->build($child);
            $item = $item?->merge($node) ?? $node;
        }

        return $item;
    }
}
