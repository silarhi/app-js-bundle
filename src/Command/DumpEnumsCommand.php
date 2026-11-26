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

namespace Silarhi\AppJsBundle\Command;

use function count;

use Silarhi\AppJsBundle\Enum\EnumDumper;
use Silarhi\AppJsBundle\Writer\GeneratedFilesWriter;

use function sprintf;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app-js:dump-enums',
    description: 'Dump the enums marked #[EnumToJs] as TypeScript or JavaScript modules',
)]
final class DumpEnumsCommand extends Command
{
    public function __construct(
        private readonly EnumDumper $enumDumper,
        private readonly GeneratedFilesWriter $writer,
        private readonly string $outputDirectory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('check', null, InputOption::VALUE_NONE, 'Fail when a file is out of date, without writing it (-v shows the diff)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $files = $this->enumDumper->getFiles();

        if (true === $input->getOption('check')) {
            $changes = $this->writer->getChanges($this->outputDirectory, $files, $this->enumDumper->getExtension());
            if ([] !== $changes) {
                $io->listing($changes);
                if ($io->isVerbose()) {
                    foreach ($changes as $path) {
                        // Raw: a type argument is no console tag
                        $io->writeln($this->writer->getDiff($this->outputDirectory, $path, $files[$path] ?? null), OutputInterface::OUTPUT_RAW);
                    }
                }
                $io->error(sprintf('%d enum file(s) out of date in "%s": run app-js:dump-enums.', count($changes), $this->outputDirectory));

                return Command::FAILURE;
            }

            $io->success(sprintf('%d enum file(s) up to date.', count($files)));

            return Command::SUCCESS;
        }

        $changes = $this->writer->write($this->outputDirectory, $files, $this->enumDumper->getExtension());
        if ($io->isVerbose()) {
            $io->listing($changes);
        }

        $io->success(sprintf('Dumped %d enum(s), %d file(s) changed.', count($files), count($changes)));

        return Command::SUCCESS;
    }
}
