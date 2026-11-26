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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\DependencyInjection\ConstantsToJsPass;
use Silarhi\AppJsBundle\Tests\Fixtures\Constants\Format;
use Silarhi\AppJsBundle\Tests\Fixtures\Constants\Internals;
use Silarhi\AppJsBundle\Tests\Fixtures\Constants\SearchLimits;
use Silarhi\AppJsBundle\Tests\Fixtures\Constants\Unmarked;
use Silarhi\AppJsBundle\Tests\Fixtures\Constants\Upload;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Needs symfony/config and symfony/dependency-injection: the "PHPUnit without the bundle dependencies" CI job removes
 * both and excludes this group.
 */
#[Group('bundle')]
final class ConstantsToJsPassTest extends TestCase
{
    public function testCollectsTheTaggedClassesByName(): void
    {
        $container = $this->createContainer();
        $container->register(Upload::class, Upload::class)->addTag(ConstantsToJsPass::TAG);
        $container->register(SearchLimits::class, SearchLimits::class)->addTag(ConstantsToJsPass::TAG, ['name' => 'Limits']);
        $container->register(Unmarked::class, Unmarked::class);

        (new ConstantsToJsPass())->process($container);

        self::assertSame(['Limits' => SearchLimits::class, 'Upload' => Upload::class], $container->getDefinition(ConstantsToJsPass::DUMPER)->getArgument('$classes'));
    }

    public function testScansTheConfiguredDirectories(): void
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 2));
        $container->setParameter(ConstantsToJsPass::PATHS, [
            '%kernel.project_dir%/tests/Fixtures/Constants' => 'Silarhi\\AppJsBundle\\Tests\\Fixtures\\Constants',
            '%kernel.project_dir%/tests/missing' => 'App\\Missing',
        ]);
        // Found both ways: once
        $container->register(Upload::class, Upload::class)->addTag(ConstantsToJsPass::TAG);

        (new ConstantsToJsPass())->process($container);

        self::assertSame([
            'Format' => Format::class,
            'Internals' => Internals::class,
            'Limits' => SearchLimits::class,
            'Upload' => Upload::class,
        ], $container->getDefinition(ConstantsToJsPass::DUMPER)->getArgument('$classes'));
    }

    public function testIgnoresPathsThatAreNoList(): void
    {
        $container = $this->createContainer();
        $container->setParameter(ConstantsToJsPass::PATHS, 'src/Model');

        (new ConstantsToJsPass())->process($container);

        self::assertSame([], $container->getDefinition(ConstantsToJsPass::DUMPER)->getArgument('$classes'));
    }

    public function testDoesNothingWithoutTheDumper(): void
    {
        $container = new ContainerBuilder();
        $container->register(Upload::class, Upload::class)->addTag(ConstantsToJsPass::TAG);

        (new ConstantsToJsPass())->process($container);

        self::assertFalse($container->hasDefinition(ConstantsToJsPass::DUMPER));
    }

    public function testRefusesATagOnSomethingThatIsNoClass(): void
    {
        $container = $this->createContainer();
        $container->register('app.missing', 'App\\Missing')->addTag(ConstantsToJsPass::TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('#[ConstantsToJs] marks classes only, "App\\Missing" is not one.');

        (new ConstantsToJsPass())->process($container);
    }

    public function testRefusesTwoClassesWithTheSameName(): void
    {
        $container = $this->createContainer();
        $container->register(Upload::class, Upload::class)->addTag(ConstantsToJsPass::TAG);
        $container->register(SearchLimits::class, SearchLimits::class)->addTag(ConstantsToJsPass::TAG, ['name' => 'Upload']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('both dump their constants as "Upload": give one a name.');

        (new ConstantsToJsPass())->process($container);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(ConstantsToJsPass::DUMPER, (new Definition())->setArgument('$classes', new AbstractArgument()));

        return $container;
    }
}
