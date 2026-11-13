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

use LogicException;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Form\FormShapeDumper;
use Silarhi\AppJsBundle\Form\Variant\FormVariant;
use Silarhi\AppJsBundle\Form\Variant\StaticFormVariantProvider;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ButtonsType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ElementsType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\InvokableListener;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\ListenersType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\RenderedItemsType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\RequiredOptionType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\StaticListenerType;
use Silarhi\AppJsBundle\Tests\Fixtures\Dumper\SwitchingType;
use Silarhi\AppJsBundle\Writer\Format;
use stdClass;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeInterface;
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
                kind: FormField<HTMLSelectElement, string | null>
                roles: FormField<HTMLSelectElement, string[]>
                size: FormField<HTMLElement, string>
                tags: FormField<HTMLElement, string[]>
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
        $this->expectExceptionMessageMatches('/^Unable to build the form "' . preg_quote(RequiredOptionType::class, '/') . '" \\(variant "0"\\): .*The required option "issuer" is missing\\.$/');

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
            $factory = new FormFactory($registry = new FormRegistry([new CoreExtension()], new ResolvedFormTypeFactory())),
            $registry,
            new ServiceLocator([SwitchingType::class => static fn (): stdClass => new stdClass()]),
            [SwitchingType::class => ['name' => 'SwitchingForm', 'dynamic' => true]],
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The variants of "' . SwitchingType::class . '" must come from a');

        $dumper->dump();
    }

    /**
     * @param array<class-string<FormTypeInterface<mixed>>, array{name: string, dynamic: bool}> $forms
     * @param array<class-string, FormVariant|list<FormVariant>>                                $variants
     */
    private function createDumper(array $forms, array $variants = [], Format $format = Format::TypeScript): FormShapeDumper
    {
        $registry = new FormRegistry([new CoreExtension()], new ResolvedFormTypeFactory());
        $providers = [];
        foreach ($variants as $formType => $variant) {
            $list = $variant instanceof FormVariant ? [$variant] : $variant;
            $providers[$formType] = static fn (): StaticFormVariantProvider => new StaticFormVariantProvider(array_map(
                static fn (FormVariant $item): array => ['options' => $item->options, 'data' => $item->data],
                $list,
            ));
        }

        return new FormShapeDumper(new FormFactory($registry), $registry, new ServiceLocator($providers), $forms, $format);
    }
}
