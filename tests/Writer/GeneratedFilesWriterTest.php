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

namespace Silarhi\AppJsBundle\Tests\Writer;

use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Writer\GeneratedFilesWriter;
use Symfony\Component\Filesystem\Filesystem;

final class GeneratedFilesWriterTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/app-js-bundle-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testWritesTheChangedFilesAndRemovesTheStaleOnes(): void
    {
        $writer = new GeneratedFilesWriter();

        self::assertSame(['A.ts', 'sub/B.ts'], $writer->write($this->directory, ['A.ts' => 'a', 'sub/B.ts' => 'b'], 'ts'));
        self::assertSame([], $writer->getChanges($this->directory, ['A.ts' => 'a', 'sub/B.ts' => 'b'], 'ts'));

        self::assertSame(['A.ts', 'sub/B.ts'], $writer->write($this->directory, ['A.ts' => 'changed'], 'ts'));
        self::assertStringEqualsFile($this->directory . '/A.ts', 'changed');
        self::assertFileDoesNotExist($this->directory . '/sub/B.ts');
    }

    public function testKeepsTheFilesWithAnotherExtension(): void
    {
        (new Filesystem())->dumpFile($this->directory . '/README.md', 'kept');
        (new Filesystem())->dumpFile($this->directory . '/Old.ts', 'kept');

        self::assertSame(['A.js'], (new GeneratedFilesWriter())->write($this->directory, ['A.js' => 'a'], 'js'));
        self::assertFileExists($this->directory . '/README.md');
        self::assertFileExists($this->directory . '/Old.ts');
    }
}
