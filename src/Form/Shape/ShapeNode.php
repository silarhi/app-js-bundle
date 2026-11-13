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

use LogicException;

use function sprintf;

/**
 * A node of a form shape. Immutable: merging returns a new node.
 *
 * @internal
 */
final readonly class ShapeNode
{
    /**
     * @param array<string, ShapeNode> $children the fields of a form, in rendering order
     * @param array<string, true>      $optional the children not always rendered
     * @param ShapeNode|null           $item     the shape of the items of a collection, null when unknown
     * @param class-string|null        $formType the form type that built a form, null when unknown or when builds disagree
     * @param string|null              $element  the DOM class a field is rendered as (HTMLSelectElement), null when unknown or when builds disagree
     */
    private function __construct(
        public ShapeKind $kind,
        public array $children = [],
        public array $optional = [],
        public ?self $item = null,
        public ?string $formType = null,
        public ?string $element = null,
    ) {
    }

    public static function field(?string $element = null): self
    {
        return new self(ShapeKind::Field, element: $element);
    }

    /**
     * @param array<string, ShapeNode> $children
     * @param class-string|null        $formType
     */
    public static function form(array $children, ?string $formType = null): self
    {
        return new self(ShapeKind::Form, $children, formType: $formType);
    }

    public static function collection(?self $item): self
    {
        return new self(ShapeKind::Collection, item: $item);
    }

    public function isOptional(string $name): bool
    {
        return isset($this->optional[$name]);
    }

    /** The same fields, rendered as the same elements, the same optional ones, in any order: the form types that built them do not matter. */
    public function equals(self $other): bool
    {
        if ($this->kind !== $other->kind) {
            return false;
        }

        if (ShapeKind::Field === $this->kind) {
            return $this->element === $other->element;
        }

        if (ShapeKind::Collection === $this->kind) {
            return $this->item instanceof self && $other->item instanceof self
                ? $this->item->equals($other->item)
                : $this->item === $other->item;
        }

        if ([] !== array_diff_key($this->children, $other->children) || [] !== array_diff_key($other->children, $this->children)) {
            return false;
        }

        if ([] !== array_diff_key($this->optional, $other->optional) || [] !== array_diff_key($other->optional, $this->optional)) {
            return false;
        }

        foreach ($this->children as $name => $child) {
            if (!$child->equals($other->children[$name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * The union of two builds of the same node: a child missing from either one becomes optional.
     *
     * @throws LogicException when the two builds disagree on the kind of a node
     */
    public function merge(self $other, string $path = ''): self
    {
        if ($this->kind !== $other->kind) {
            throw new LogicException(sprintf('The node "%s" is a %s in a build and a %s in another.', $path, strtolower($this->kind->name), strtolower($other->kind->name)));
        }

        return match ($this->kind) {
            ShapeKind::Field => $this->element === $other->element ? $this : self::field(),
            ShapeKind::Collection => self::collection(match (true) {
                !$this->item instanceof self => $other->item,
                !$other->item instanceof self => $this->item,
                default => $this->item->merge($other->item, ('' === $path ? '' : $path . '.') . '{index}'),
            }),
            ShapeKind::Form => $this->mergeChildren($other, $path),
        };
    }

    private function mergeChildren(self $other, string $path): self
    {
        $children = $this->children;
        $optional = $this->optional + $other->optional;
        foreach ($other->children as $name => $child) {
            $children[$name] = isset($children[$name])
                ? $children[$name]->merge($child, ('' === $path ? '' : $path . '.') . $name)
                : $child;
        }

        foreach (array_keys($children) as $name) {
            if (!isset($this->children[$name]) || !isset($other->children[$name])) {
                $optional[$name] = true;
            }
        }

        return new self(ShapeKind::Form, $children, $optional, formType: $this->formType === $other->formType ? $this->formType : null);
    }
}
