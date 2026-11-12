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
     */
    private function __construct(
        public ShapeKind $kind,
        public array $children = [],
        public array $optional = [],
        public ?self $item = null,
    ) {
    }

    public static function field(): self
    {
        return new self(ShapeKind::Field);
    }

    /**
     * @param array<string, ShapeNode> $children
     */
    public static function form(array $children): self
    {
        return new self(ShapeKind::Form, $children);
    }

    public static function collection(?self $item): self
    {
        return new self(ShapeKind::Collection, item: $item);
    }

    public function isOptional(string $name): bool
    {
        return isset($this->optional[$name]);
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
            ShapeKind::Field => $this,
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

        return new self(ShapeKind::Form, $children, $optional);
    }
}
