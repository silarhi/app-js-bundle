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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\DependencyInjection\FormToJsPass;
use Silarhi\AppJsBundle\Form\Variant\MatrixFormVariantProvider;
use Silarhi\AppJsBundle\Tests\Fixtures\Form\LeaseFormVariants;
use Silarhi\AppJsBundle\Tests\Fixtures\Form\LeaseType;
use Silarhi\AppJsBundle\Tests\Fixtures\Form\NotExposedType;
use Silarhi\AppJsBundle\Tests\Fixtures\Form\UserType;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\DuplicateNameType;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\InvalidMatrixType;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\NotAProviderType;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class FormToJsPassTest extends TestCase
{
    public function testCollectsTheMarkedFormTypes(): void
    {
        $container = $this->createContainer([UserType::class, LeaseType::class, NotExposedType::class]);

        (new FormToJsPass())->process($container);

        self::assertSame([
            LeaseType::class => ['name' => 'Lease', 'dynamic' => true],
            UserType::class => ['name' => 'UserForm', 'dynamic' => true],
        ], $container->getDefinition(FormToJsPass::DUMPER)->getArgument('$forms'));
        // A provider outside the service resources is registered
        self::assertTrue($container->hasDefinition(LeaseFormVariants::class));
        // The matrix of UserType is given to a provider of its own
        $locator = $container->getDefinition(FormToJsPass::DUMPER)->getArgument('$variantProviders');
        self::assertInstanceOf(Reference::class, $locator);
        $providers = $container->getDefinition((string) $locator)->getArgument(0);
        self::assertIsArray($providers);
        self::assertEqualsCanonicalizing([LeaseType::class, UserType::class], array_keys($providers));
        $argument = $providers[UserType::class];
        self::assertInstanceOf(ServiceClosureArgument::class, $argument);
        $reference = $argument->getValues()[0];
        self::assertInstanceOf(Reference::class, $reference);
        $userProvider = $container->getDefinition((string) $reference);
        self::assertSame(MatrixFormVariantProvider::class, $userProvider->getClass());
        self::assertSame([[], ['with_siren' => [true, false]], null], $userProvider->getArguments());
    }

    public function testDoesNothingWithoutSymfonyForm(): void
    {
        $container = new ContainerBuilder();

        (new FormToJsPass())->process($container);

        self::assertFalse($container->hasDefinition(FormToJsPass::DUMPER));
    }

    public function testSkipsTheFormTypesWhoseClassIsNoString(): void
    {
        $container = $this->createContainer([UserType::class]);
        $container->setParameter('form_types', [UserType::class]);
        $container->register('dynamic_type', '%form_types%')->addTag('form.type');

        (new FormToJsPass())->process($container);

        $forms = $container->getDefinition(FormToJsPass::DUMPER)->getArgument('$forms');
        self::assertIsArray($forms);
        self::assertSame([UserType::class], array_keys($forms));
    }

    public function testRemovesTheFormServicesWithoutTheFormIntegration(): void
    {
        $container = $this->createContainer([UserType::class]);
        $container->removeDefinition('form.factory');

        (new FormToJsPass())->process($container);

        self::assertFalse($container->hasDefinition(FormToJsPass::DUMPER));
        self::assertFalse($container->hasDefinition(FormToJsPass::COMMAND));
    }

    /**
     * @param list<class-string> $formTypes
     */
    #[DataProvider('invalidProvider')]
    public function testRefusesInvalidAttributes(array $formTypes, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new FormToJsPass())->process($this->createContainer($formTypes));
    }

    /**
     * @return iterable<string, array{list<class-string>, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'invalid matrix' => [[InvalidMatrixType::class], 'Invalid #[FormToJs] on "' . InvalidMatrixType::class . '": The option "with_siren" of the matrix must be a non-empty list of values, an empty array given.'];
        yield 'not a provider' => [[NotAProviderType::class], 'must be the class of a'];
        yield 'same name' => [[UserType::class, DuplicateNameType::class], 'are both exposed as "UserForm": give one a name.'];
    }

    /**
     * @param list<class-string> $formTypes
     */
    private function createContainer(array $formTypes): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('form.factory', new Definition());
        $container->setDefinition('form.registry', new Definition());
        $container->setDefinition(FormToJsPass::COMMAND, new Definition());
        $container->setDefinition(FormToJsPass::DUMPER, (new Definition())
            ->setArgument('$variantProviders', new AbstractArgument())
            ->setArgument('$forms', new AbstractArgument()));

        foreach ($formTypes as $formType) {
            $container->register($formType, $formType)->addTag('form.type');
        }

        return $container;
    }
}
