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

namespace Silarhi\AppJsBundle\Tests\Enum;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Enum\EnumDumper;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Kind;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Placeholder;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Status;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\VatRate;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\ObjectResultEnum;
use Silarhi\AppJsBundle\Tests\Fixtures\Invalid\TakenExportEnum;
use Silarhi\AppJsBundle\Writer\Format;

final class EnumDumperTest extends TestCase
{
    public function testDumpsAModulePerEnum(): void
    {
        $files = $this->createDumper()->getFiles();

        self::assertSame(['Kind.ts', 'Status.ts', 'Vat.ts'], array_keys($files));
    }

    public function testRendersAsBiomeFormatsIt(): void
    {
        $files = $this->createDumper()->getFiles();

        self::assertSame(<<<'TS'
            export const DRAFT_NAME = 'Draft'
            export const E_INVOICE_NAME = 'EInvoice'
            export const QUOTED_NAME = 'Quoted'

            const Status = Object.freeze({
                Draft: 'draft',
                EInvoice: 'e_invoice',
                Quoted: "it's",
            })
            export default Status

            export type StatusValue = (typeof Status)[keyof typeof Status]

            TS, $files['Status.ts']);
    }

    public function testRendersIntegerAndUnitEnums(): void
    {
        $files = $this->createDumper()->getFiles();

        self::assertStringContainsString("const Vat = Object.freeze({\n    Standard: 2000,\n    Zero: 0,\n})\nexport default Vat\n", $files['Vat.ts']);
        self::assertStringContainsString("    Text: 'Text',\n    Date: 'Date',\n", $files['Kind.ts']);
    }

    public function testDumpsTheSameCodeAsJavaScriptWithAJsDocType(): void
    {
        $javaScript = $this->createDumper(Format::JavaScript)->getFiles();

        self::assertSame(['Kind.js', 'Status.js', 'Vat.js'], array_keys($javaScript));
        self::assertSame(
            str_replace(
                'export type StatusValue = (typeof Status)[keyof typeof Status]',
                '/** @typedef {(typeof Status)[keyof typeof Status]} StatusValue */',
                $this->createDumper()->getFiles()['Status.ts'],
            ),
            $javaScript['Status.js'],
        );
    }

    public function testDumpsTheResultsOfTheMethodsByCaseValue(): void
    {
        $files = (new EnumDumper(['Status' => Status::class], methods: ['Status' => ['label', 'isFinal']]))->getFiles();

        self::assertStringEndsWith(<<<'TS'
            export type StatusValue = (typeof Status)[keyof typeof Status]

            export const LABEL = Object.freeze({
                draft: 'status.Draft',
                e_invoice: 'status.EInvoice',
                "it's": 'status.Quoted',
            })

            export const IS_FINAL = Object.freeze({
                draft: false,
                e_invoice: true,
                "it's": true,
            })

            TS, $files['Status.ts']);
    }

    public function testRefusesAResultWithoutLiteral(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The result of ' . ObjectResultEnum::class . '::One->getOwner() cannot be dumped: a stdClass has no JavaScript literal.');

        (new EnumDumper(['Owner' => ObjectResultEnum::class], methods: ['Owner' => ['getOwner']]))->getFiles();
    }

    public function testRefusesAMethodWhoseExportIsTaken(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to dump the method "draftName()" of the enum "Taken": its export "DRAFT_NAME" is already taken.');

        (new EnumDumper(['Taken' => TakenExportEnum::class], methods: ['Taken' => ['draftName']]))->getFiles();
    }

    public function testSkipsTheEnumsWithoutCase(): void
    {
        self::assertSame([], (new EnumDumper(['Placeholder' => Placeholder::class]))->getFiles());
    }

    private function createDumper(Format $format = Format::TypeScript): EnumDumper
    {
        return new EnumDumper(['Kind' => Kind::class, 'Status' => Status::class, 'Vat' => VatRate::class], $format);
    }
}
