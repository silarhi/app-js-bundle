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

namespace Silarhi\AppJsBundle\Tests\DependencyInjection;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\DependencyInjection\EnumToJsPass;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Kind;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Status;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\VatRate;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\NotAnEnum;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class EnumToJsPassTest extends TestCase
{
    public function testCollectsTheTaggedEnumsByName(): void
    {
        $container = $this->createContainer();
        $container->register(Status::class, Status::class)->addTag(EnumToJsPass::TAG);
        $container->register(VatRate::class, VatRate::class)->addTag(EnumToJsPass::TAG, ['name' => 'Vat']);
        $container->register(Kind::class, Kind::class);

        (new EnumToJsPass())->process($container);

        self::assertSame(['Status' => Status::class, 'Vat' => VatRate::class], $container->getDefinition(EnumToJsPass::DUMPER)->getArgument('$enums'));
    }

    public function testDoesNothingWithoutTheDumper(): void
    {
        $container = new ContainerBuilder();
        $container->register(NotAnEnum::class, NotAnEnum::class)->addTag(EnumToJsPass::TAG);

        (new EnumToJsPass())->process($container);

        self::assertFalse($container->hasDefinition(EnumToJsPass::DUMPER));
    }

    public function testRefusesAClassThatIsNoEnum(): void
    {
        $container = $this->createContainer();
        $container->register(NotAnEnum::class, NotAnEnum::class)->addTag(EnumToJsPass::TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('#[EnumToJs] marks enums only, "' . NotAnEnum::class . '" is not one.');

        (new EnumToJsPass())->process($container);
    }

    public function testRefusesTwoEnumsWithTheSameName(): void
    {
        $container = $this->createContainer();
        $container->register(Status::class, Status::class)->addTag(EnumToJsPass::TAG);
        $container->register(VatRate::class, VatRate::class)->addTag(EnumToJsPass::TAG, ['name' => 'Status']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('are both dumped as "Status": give one a name.');

        (new EnumToJsPass())->process($container);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(EnumToJsPass::DUMPER, (new Definition())->setArgument('$enums', new AbstractArgument()));

        return $container;
    }
}
