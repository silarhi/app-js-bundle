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

use function dirname;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\DependencyInjection\EnumToJsPass;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Kind;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Placeholder;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Status;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\VatRate;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\NotAnEnum;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\ParameterMethodEnum;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\StaticMethodEnum;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\UnknownMethodEnum;
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
        self::assertSame(['Status' => ['label', 'isFinal']], $container->getDefinition(EnumToJsPass::DUMPER)->getArgument('$methods'));
    }

    /**
     * @param class-string $enum
     */
    #[DataProvider('invalidMethodProvider')]
    public function testRefusesAMethodTheDumpCannotCall(string $enum, string $message): void
    {
        $container = $this->createContainer();
        $container->register($enum, $enum)->addTag(EnumToJsPass::TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new EnumToJsPass())->process($container);
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function invalidMethodProvider(): iterable
    {
        yield 'unknown' => [UnknownMethodEnum::class, 'The #[EnumToJs] method "missing" of "' . UnknownMethodEnum::class . '" does not exist.'];
        yield 'static' => [StaticMethodEnum::class, 'The #[EnumToJs] method "' . StaticMethodEnum::class . '::getDefault()" must be public, not static, and take no required parameter.'];
        yield 'required parameter' => [ParameterMethodEnum::class, 'The #[EnumToJs] method "' . ParameterMethodEnum::class . '::describe()" must be public'];
    }

    public function testScansTheConfiguredDirectories(): void
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 2));
        $container->setParameter(EnumToJsPass::PATHS, [
            '%kernel.project_dir%/tests/Fixtures/Enum' => 'Silarhi\\AppJsBundle\\Tests\\Fixtures\\Enum',
            '%kernel.project_dir%/tests/missing' => 'App\\Missing',
        ]);
        // Found both ways: once
        $container->register(Status::class, Status::class)->addTag(EnumToJsPass::TAG);

        (new EnumToJsPass())->process($container);

        self::assertSame([
            'Kind' => Kind::class,
            'Placeholder' => Placeholder::class,
            'Status' => Status::class,
            'Vat' => VatRate::class,
        ], $container->getDefinition(EnumToJsPass::DUMPER)->getArgument('$enums'));
    }

    public function testIgnoresPathsThatAreNoList(): void
    {
        $container = $this->createContainer();
        $container->setParameter(EnumToJsPass::PATHS, 'src/Enum');

        (new EnumToJsPass())->process($container);

        self::assertSame([], $container->getDefinition(EnumToJsPass::DUMPER)->getArgument('$enums'));
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
        $container->setDefinition(EnumToJsPass::DUMPER, (new Definition())->setArgument('$enums', new AbstractArgument())->setArgument('$methods', new AbstractArgument()));

        return $container;
    }
}
