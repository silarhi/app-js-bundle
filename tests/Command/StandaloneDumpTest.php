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

namespace Silarhi\AppJsBundle\Tests\Command;

use function dirname;

use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Command\DumpCommand;
use Silarhi\AppJsBundle\Command\DumpConstantsCommand;
use Silarhi\AppJsBundle\Command\DumpEnumsCommand;
use Silarhi\AppJsBundle\Constants\ConstantsDumper;
use Silarhi\AppJsBundle\Enum\EnumDumper;
use Silarhi\AppJsBundle\Writer\Format;
use Silarhi\AppJsBundle\Writer\GeneratedFilesWriter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The commands wired by hand, as an application without the bundle (nor symfony/dependency-injection) does.
 */
final class StandaloneDumpTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = dirname(__DIR__, 2) . '/var/cache/standalone';
        (new Filesystem())->remove($this->directory);
    }

    public function testDumpsTheEnumsAndTheConstantsThenChecksThem(): void
    {
        $fixtures = __DIR__ . '/../Fixtures';
        $writer = new GeneratedFilesWriter();
        $application = new Application();
        $application->addCommands([
            new DumpCommand(),
            new DumpEnumsCommand(EnumDumper::fromDirectories([$fixtures . '/Enum' => 'Silarhi\\AppJsBundle\\Tests\\Fixtures\\Enum'], Format::JavaScript), $writer, $this->directory . '/enums'),
            new DumpConstantsCommand(ConstantsDumper::fromDirectories([$fixtures . '/Constants' => 'Silarhi\\AppJsBundle\\Tests\\Fixtures\\Constants'], Format::JavaScript), $writer, $this->directory . '/constants'),
        ]);

        $tester = new CommandTester($application->find('app-js:dump'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertFileExists($this->directory . '/enums/Status.js');
        self::assertFileExists($this->directory . '/enums/Vat.js');
        self::assertFileExists($this->directory . '/constants/Limits.js');
        self::assertStringContainsString("export const MAX_SIZE = 2000000\n", (string) file_get_contents($this->directory . '/constants/Upload.js'));

        $check = new CommandTester($application->find('app-js:dump'));
        self::assertSame(Command::SUCCESS, $check->execute(['--check' => true, '--strict' => true]));
    }
}
