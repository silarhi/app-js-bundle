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

use Silarhi\AppJsBundle\Constants\ConstantsDumper;
use Silarhi\AppJsBundle\Writer\GeneratedFilesWriter;

use function sprintf;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(
    name: 'app-js:dump-constants',
    description: 'Dump the public constants of the classes marked #[ConstantsToJs] as TypeScript or JavaScript modules',
)]
final class DumpConstantsCommand extends Command
{
    public function __construct(
        private readonly ConstantsDumper $constantsDumper,
        private readonly GeneratedFilesWriter $writer,
        private readonly string $outputDirectory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('check', null, InputOption::VALUE_NONE, 'Fail when a file is out of date, without writing it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $files = $this->constantsDumper->getFiles();

        if (true === $input->getOption('check')) {
            $changes = $this->writer->getChanges($this->outputDirectory, $files, $this->constantsDumper->getExtension());
            if ([] !== $changes) {
                $io->listing($changes);
                $io->error(sprintf('%d constants file(s) out of date in "%s": run app-js:dump-constants.', count($changes), $this->outputDirectory));

                return Command::FAILURE;
            }

            $io->success(sprintf('%d constants file(s) up to date.', count($files)));

            return Command::SUCCESS;
        }

        $changes = $this->writer->write($this->outputDirectory, $files, $this->constantsDumper->getExtension());
        if ($io->isVerbose()) {
            $io->listing($changes);
        }

        $io->success(sprintf('Dumped the constants of %d class(es), %d file(s) changed.', count($files), count($changes)));

        return Command::SUCCESS;
    }
}
