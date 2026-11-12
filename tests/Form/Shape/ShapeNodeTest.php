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

namespace Silarhi\AppJsBundle\Tests\Form\Shape;

use LogicException;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Form\Shape\ShapeNode;

final class ShapeNodeTest extends TestCase
{
    public function testAChildMissingFromABuildBecomesOptional(): void
    {
        $merged = ShapeNode::form(['name' => ShapeNode::field(), 'siren' => ShapeNode::field()])
            ->merge(ShapeNode::form(['name' => ShapeNode::field(), 'vat' => ShapeNode::field()]));

        self::assertSame(['name', 'siren', 'vat'], array_keys($merged->children));
        self::assertFalse($merged->isOptional('name'));
        self::assertTrue($merged->isOptional('siren'));
        self::assertTrue($merged->isOptional('vat'));
    }

    public function testOptionalStaysOptional(): void
    {
        $optional = ShapeNode::form(['a' => ShapeNode::field()])->merge(ShapeNode::form([]));

        self::assertTrue($optional->merge(ShapeNode::form(['a' => ShapeNode::field()]))->isOptional('a'));
    }

    public function testMergesCollectionItems(): void
    {
        $merged = ShapeNode::collection(null)
            ->merge(ShapeNode::collection(ShapeNode::form(['number' => ShapeNode::field()])))
            ->merge(ShapeNode::collection(ShapeNode::form(['number' => ShapeNode::field(), 'type' => ShapeNode::field()])));

        self::assertNotNull($merged->item);
        self::assertTrue($merged->item->isOptional('type'));
        self::assertFalse($merged->item->isOptional('number'));
    }

    public function testRefusesNodesOfDifferentKinds(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The node "address.city" is a field in a build and a form in another.');

        ShapeNode::form(['address' => ShapeNode::form(['city' => ShapeNode::field()])])
            ->merge(ShapeNode::form(['address' => ShapeNode::form(['city' => ShapeNode::form([])])]));
    }
}
