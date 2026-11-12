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

use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Enum\EnumDumper;
use Silarhi\AppJsBundle\Enum\EnumLocator;
use Silarhi\AppJsBundle\Writer\Format;

final class EnumDumperTest extends TestCase
{
    public function testDumpsOnlyTheEnumsMarkedEnumToJs(): void
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

            TS, $files['Status.ts']);
    }

    public function testRendersIntegerAndUnitEnums(): void
    {
        $files = $this->createDumper()->getFiles();

        self::assertStringContainsString("const Vat = Object.freeze({\n    Standard: 2000,\n    Zero: 0,\n})\nexport default Vat\n", $files['Vat.ts']);
        self::assertStringContainsString("    Text: 'Text',\n    Date: 'Date',\n", $files['Kind.ts']);
    }

    public function testDumpsTheSameCodeAsJavaScript(): void
    {
        $javaScript = $this->createDumper(Format::JavaScript)->getFiles();

        self::assertSame(['Kind.js', 'Status.js', 'Vat.js'], array_keys($javaScript));
        self::assertSame($this->createDumper()->getFiles()['Status.ts'], $javaScript['Status.js']);
    }

    public function testSkipsMissingDirectories(): void
    {
        self::assertSame([], (new EnumDumper(new EnumLocator([__DIR__ . '/missing' => 'App\\Enum'])))->getFiles());
    }

    private function createDumper(Format $format = Format::TypeScript): EnumDumper
    {
        return new EnumDumper(new EnumLocator([
            __DIR__ . '/../Fixtures/Enum' => 'Silarhi\\AppJsBundle\\Tests\\Fixtures\\Enum\\',
        ]), $format);
    }
}
