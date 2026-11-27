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

namespace Silarhi\AppJsBundle\Tests\Form;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Silarhi\AppJsBundle\Form\FormShapeDumper;
use Silarhi\AppJsBundle\Form\Variant\FormVariant;
use Silarhi\AppJsBundle\Form\Variant\FormVariantProviderInterface;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\AbstractItem;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ButtonsType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ChoicesType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\DataClassType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\DataListenerType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ElementsType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\InvokableListener;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\Item;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ListenersType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\NamedItem;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\PlaceType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\RenderedItemsType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\RequiredOptionType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\StaticListenerType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\SwitchingType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\UnreachableDatabaseDriver;
use Silarhi\AppJsBundle\Tests\Fixtures\ServiceLocator;
use Silarhi\AppJsBundle\Writer\Format;
use stdClass;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\ResolvedFormTypeFactory;

final class FormShapeDumperTest extends TestCase
{
    public function testReportsEveryKindOfDataListener(): void
    {
        $dump = $this->createDumper([ListenersType::class => ['name' => 'ListenersForm', 'dynamic' => true]])->dump();

        self::assertCount(1, $dump->warnings);
        self::assertStringContainsString('"(root)" from ' . ListenersType::class, $dump->warnings[0]);
        self::assertStringContainsString('"child" from ' . InvokableListener::class, $dump->warnings[0]);
        self::assertStringContainsString('"items.{index}" from ' . InvokableListener::class, $dump->warnings[0]);
        self::assertStringNotContainsString(StaticListenerType::class, $dump->warnings[0]);
    }

    public function testDynamicFalseSilencesTheWarning(): void
    {
        $dump = $this->createDumper([ListenersType::class => ['name' => 'ListenersForm', 'dynamic' => false]])->dump();

        self::assertSame([], $dump->warnings);
    }

    public function testReadsTheDeclaredFieldsAsOptional(): void
    {
        $dump = $this->createDumper([ButtonsType::class => ['name' => 'ButtonsForm', 'dynamic' => true, 'optional' => ['name', 'actions.{index}.label']]])->dump();

        self::assertStringContainsString(
            "    name?: FormField<HTMLInputElement, string>\n    actions: FormCollection<{\n        label?: FormField<HTMLInputElement, string>\n        remove: FormField<HTMLButtonElement>\n    }>\n",
            $dump->files['ButtonsForm.ts'],
        );
    }

    public function testFailsOnAnOptionalFieldOfNoBuild(): void
    {
        $dumper = $this->createDumper([ButtonsType::class => ['name' => 'ButtonsForm', 'dynamic' => true, 'optional' => ['title']]]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The #[FormToJs] optional field "title" of "' . ButtonsType::class . '" is in no build: No field "title".');

        $dumper->dump();
    }

    public function testDumpsTheButtonsAsFields(): void
    {
        $dump = $this->createDumper([ButtonsType::class => ['name' => 'ButtonsForm', 'dynamic' => true]])->dump();

        self::assertSame([], $dump->warnings);
        self::assertStringContainsString(
            "    name: FormField<HTMLInputElement, string>\n    actions: FormCollection<{\n        label: FormField<HTMLInputElement, string>\n        remove: FormField<HTMLButtonElement>\n    }>\n    preview: FormField<HTMLButtonElement>\n    generate: FormField<HTMLButtonElement>\n",
            $dump->files['ButtonsForm.ts'],
        );
    }

    public function testTypesTheElementAndTheValueOfEachField(): void
    {
        $dump = $this->createDumper([ElementsType::class => ['name' => 'ElementsForm', 'dynamic' => true]])->dump();

        self::assertStringContainsString(<<<'TS'
            export interface ElementsForm {
                title: FormField<HTMLInputElement, string>
                body: FormField<HTMLTextAreaElement, string>
                kind: FormField<HTMLSelectElement, 'a' | null>
                roles: FormField<HTMLSelectElement, 'a'[]>
                size: FormField<HTMLElement, 's'>
                tags: FormField<HTMLElement, 'a'[]>
                agree: FormField<HTMLInputElement, boolean>
                yes: FormField<HTMLInputElement, string | undefined>
                amount: FormField<HTMLInputElement, string>
                startsAt: FormField<HTMLInputElement, string>
                birthday: {
                    year: FormField<HTMLSelectElement, string | null>
                    month: FormField<HTMLSelectElement, string | null>
                    day: FormField<HTMLSelectElement, string | null>
                }
                code: FormField
                save: FormField<HTMLButtonElement>
            }
            TS, $dump->files['ElementsForm.ts']);
    }

    public function testBuildsANewDataClassWithoutData(): void
    {
        $dump = $this->createDumper([DataClassType::class => ['name' => 'DataClassForm', 'dynamic' => true]])->dump();

        // buildForm() reads it, its PRE_SET_DATA listener adds a field: no warning, the dump had data
        self::assertSame([], $dump->warnings);
        self::assertStringContainsString("    name: FormField<HTMLInputElement, string>\n    fromData: FormField<HTMLInputElement, string>\n    fromListener: FormField<HTMLInputElement, string>\n", $dump->files['DataClassForm.ts']);
    }

    public function testKeepsTheDataOfTheVariant(): void
    {
        $dump = $this->createDumper(
            [DataClassType::class => ['name' => 'DataClassForm', 'dynamic' => true]],
            [DataClassType::class => [new FormVariant(['data_class' => null], ['name' => 'a'])]],
        )->dump();

        self::assertStringContainsString('fromData: FormField', $dump->files['DataClassForm.ts']);
    }

    public function testNeverBuildsATypeWithoutDataBeforeItsEmptyData(): void
    {
        $dump = $this->createDumper([DataListenerType::class => ['name' => 'DataListenerForm', 'dynamic' => true]])->dump();

        self::assertStringContainsString('    name: FormField<HTMLInputElement, string>', $dump->files['DataListenerForm.ts']);
    }

    public function testBuildsTheEmptyDataOfTheType(): void
    {
        $dump = $this->createDumper(
            [DataClassType::class => ['name' => 'DataClassForm', 'dynamic' => true]],
            [DataClassType::class => [new FormVariant(['data_class' => NamedItem::class, 'empty_data' => static fn (): NamedItem => new NamedItem('new')])]],
        )->dump();

        self::assertSame([], $dump->warnings);
        self::assertSame([], $dump->notices);
        self::assertStringContainsString("    fromData: FormField<HTMLInputElement, string>\n    fromListener: FormField<HTMLInputElement, string>\n", $dump->files['DataClassForm.ts']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string|null}>
     */
    public static function provideEmptyDataThatIsNoData(): iterable
    {
        yield 'a constructor argument' => [['data_class' => NamedItem::class], 'Too few arguments'];
        yield 'abstract' => [['data_class' => AbstractItem::class], 'Cannot instantiate abstract class'];
        yield 'a failing empty_data' => [['empty_data' => static fn (): never => throw new RuntimeException('No client submitted')], 'No client submitted'];
        yield 'no data_class' => [['data_class' => null], null];
        yield 'not required' => [['required' => false], null];
        yield 'a value' => [['empty_data' => 'none'], null];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideEmptyDataThatIsNoData')]
    public function testBuildsWithoutDataAnEmptyDataThatIsNoObject(array $options, ?string $notice): void
    {
        $dump = $this->createDumper(
            [DataClassType::class => ['name' => 'DataClassForm', 'dynamic' => true]],
            [DataClassType::class => [new FormVariant($options)]],
        )->dump();

        self::assertStringNotContainsString('fromData', $dump->files['DataClassForm.ts']);
        self::assertStringNotContainsString('fromListener', $dump->files['DataClassForm.ts']);
        self::assertCount(1, $dump->warnings);
        if (null === $notice) {
            self::assertSame([], $dump->notices);
        } else {
            self::assertCount(1, $dump->notices);
            self::assertStringStartsWith(DataClassType::class . ' (variant "0") is built without data: its empty data failed (', $dump->notices[0]);
            self::assertStringContainsString($notice, $dump->notices[0]);
        }
    }

    public function testBuildsANewDataClassForEachVariantWithoutData(): void
    {
        $dump = $this->createDumper(
            [DataClassType::class => ['name' => 'DataClassForm', 'dynamic' => true]],
            [DataClassType::class => [new FormVariant(['data_class' => Item::class]), new FormVariant(['data_class' => NamedItem::class])]],
        )->dump();

        self::assertStringContainsString("    fromData?: FormField<HTMLInputElement, string>\n", $dump->files['DataClassForm.ts']);
    }

    public function testReadsTheItemShapeFromTheRenderedItems(): void
    {
        $dumper = $this->createDumper(
            [RenderedItemsType::class => ['name' => 'RenderedItemsForm', 'dynamic' => true]],
            [RenderedItemsType::class => new FormVariant(data: ['phones' => [['number' => '01'], ['number' => '02', 'fax' => '03']]])],
        );

        self::assertStringContainsString(
            "    phones: FormCollection<{\n        number: FormField<HTMLInputElement, string>\n        fax?: FormField<HTMLInputElement, string>\n    }>\n",
            $dumper->dump()->files['RenderedItemsForm.ts'],
        );
    }

    public function testDumpsDeclarationsForJavaScript(): void
    {
        $dumper = $this->createDumper([SwitchingType::class => ['name' => 'SwitchingForm', 'dynamic' => true]], format: Format::JavaScript);

        self::assertSame('d.ts', $dumper->getExtension());
        self::assertSame(['SwitchingForm.d.ts'], array_keys($dumper->dump()->files));
    }

    public function testFailsOnAFormThatCannotBeBuilt(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/^Unable to build the form "' . preg_quote(RequiredOptionType::class, '/') . '" \\(variant "default"\\): .*The required option "issuer" is missing\\.$/');

        $this->createDumper([RequiredOptionType::class => ['name' => 'RequiredOptionForm', 'dynamic' => true]])->dump();
    }

    public function testFailsOnVariantsThatDisagree(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to merge the variants of "' . SwitchingType::class . '": The node "address" is a field in a build and a form in another.');

        $this->createDumper(
            [SwitchingType::class => ['name' => 'SwitchingForm', 'dynamic' => true]],
            [SwitchingType::class => [new FormVariant(), new FormVariant(['detailed' => true])]],
        )->dump();
    }

    public function testFailsWithoutVariant(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The form type "' . SwitchingType::class . '" has no variant to build.');

        $this->createDumper([SwitchingType::class => ['name' => 'SwitchingForm', 'dynamic' => true]], [SwitchingType::class => []])->dump();
    }

    public function testFailsOnAProviderOfTheWrongType(): void
    {
        $dumper = new FormShapeDumper(
            new FormRegistry([new CoreExtension()], new ResolvedFormTypeFactory()),
            new ServiceLocator([SwitchingType::class => static fn (): stdClass => new stdClass()]),
            [SwitchingType::class => ['name' => 'SwitchingForm', 'dynamic' => true]],
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The variants of "' . SwitchingType::class . '" must come from a');

        $dumper->dump();
    }

    public function testTypesTheValuesOfTheChoicesAsBiomeFormatsThem(): void
    {
        $dump = $this->createDumper(
            [ChoicesType::class => ['name' => 'ChoicesForm', 'dynamic' => true]],
            [ChoicesType::class => [new FormVariant(), new FormVariant(['extended' => true])]],
        )->dump();

        self::assertSame(<<<'TS'
            // Generated by app-js:dump-forms from Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ChoicesType, do not edit
            import type { FormCollection, FormField } from '@silarhi/app-js/form'

            export interface ChoicesForm {
                status: FormField<HTMLSelectElement, 'draft' | 'e_invoice' | "it's" | null>
                optional: FormField<HTMLSelectElement, 'a' | 'b' | '' | null>
                grouped: FormField<HTMLSelectElement, 'a' | 'b' | 'c' | null>
                radios: FormField<HTMLElement, 'x' | 'y' | ''>
                owner: FormField<HTMLSelectElement, string | null>
                none: FormField<HTMLSelectElement, string | null>
                level: FormField<HTMLSelectElement, '1' | '2' | '3' | null>
                middle: FormField<
                    HTMLSelectElement,
                    'draft' | 'active' | 'terminated' | 'suspended' | 'archived' | 'pending_validation' | null
                >
                longOne: FormField<
                    HTMLSelectElement,
                    | 'alpha_one'
                    | 'beta_two'
                    | 'gamma_three'
                    | 'delta_four'
                    | 'epsilon_five'
                    | 'zeta_six'
                    | 'eta_seven'
                    | 'theta_eight'
                    | null
                >
                longMany: FormField<
                    HTMLSelectElement,
                    (
                        | 'alpha_one'
                        | 'beta_two'
                        | 'gamma_three'
                        | 'delta_four'
                        | 'epsilon_five'
                        | 'zeta_six'
                        | 'eta_seven'
                        | 'theta_eight'
                    )[]
                >
                longItems: FormCollection<
                    FormField<HTMLSelectElement, 'alpha_one' | 'beta_two' | 'gamma_three' | 'delta_four' | 'epsilon_five' | null>
                >
                longerItems: FormCollection<
                    FormField<
                        HTMLSelectElement,
                        | 'alpha_one'
                        | 'beta_two'
                        | 'gamma_three'
                        | 'delta_four'
                        | 'epsilon_five'
                        | 'zeta_six'
                        | 'eta_seven'
                        | 'theta_eight'
                        | null
                    >
                >
            }

            TS, $dump->files['ChoicesForm.ts']);
    }

    public function testBuildsTheEntityFieldsWithoutQueryingTheDatabase(): void
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/../Fixtures/Dumper']));
        if (\PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('Silarhi\\AppJsBundle\\Tests\\Proxies');
        }

        $entityManager = new EntityManager(DriverManager::getConnection(['driverClass' => UnreachableDatabaseDriver::class]), $config);
        $managerRegistry = self::createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($entityManager);

        $registry = new FormRegistry([
            new CoreExtension(),
            new PreloadedExtension([new EntityType($managerRegistry)], []),
        ], new ResolvedFormTypeFactory());
        $dump = (new FormShapeDumper($registry, new ServiceLocator([]), [PlaceType::class => ['name' => 'PlaceForm', 'dynamic' => true]]))->dump();

        self::assertStringContainsString(<<<'TS'
            export interface PlaceForm {
                name: FormField<HTMLInputElement, string>
                country: FormField<HTMLSelectElement, string | null>
            }
            TS, $dump->files['PlaceForm.ts']);
        // The application's own form factory still loads the choices from the database
        $this->expectExceptionMessage('The dump connected to the database.');
        (new FormFactory($registry))->create(PlaceType::class)->createView();
    }

    /**
     * @param array<class-string<FormTypeInterface<mixed>>, array{name: string, dynamic: bool, optional?: list<string>}> $forms
     * @param array<class-string, FormVariant|list<FormVariant>>                                                         $variants
     */
    private function createDumper(array $forms, array $variants = [], Format $format = Format::TypeScript): FormShapeDumper
    {
        $registry = new FormRegistry([new CoreExtension()], new ResolvedFormTypeFactory());
        $providers = [];
        foreach ($variants as $formType => $variant) {
            $list = $variant instanceof FormVariant ? [$variant] : $variant;
            $providers[$formType] = static fn (): FormVariantProviderInterface => new class($list) implements FormVariantProviderInterface {
                /**
                 * @param list<FormVariant> $variants
                 */
                public function __construct(private readonly array $variants)
                {
                }

                public function getVariants(): iterable
                {
                    return $this->variants;
                }
            };
        }

        return new FormShapeDumper($registry, new ServiceLocator($providers), $forms, $format);
    }
}
