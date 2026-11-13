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

use Silarhi\AppJsBundle\Form\FormShapeDumper;
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
    name: 'app-js:dump-forms',
    description: 'Dump the shapes of the forms marked #[FormToJs] as TypeScript interfaces',
)]
final class DumpFormsCommand extends Command
{
    public function __construct(
        private readonly FormShapeDumper $formShapeDumper,
        private readonly GeneratedFilesWriter $writer,
        private readonly string $outputDirectory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('check', null, InputOption::VALUE_NONE, 'Fail when a file is out of date, without writing it')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Fail on the warnings of forms whose shape may miss fields');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dump = $this->formShapeDumper->dump();

        foreach ($dump->warnings as $warning) {
            $io->warning($warning);
        }

        if ($io->isVerbose()) {
            foreach ($dump->notices as $notice) {
                $io->note($notice);
            }
        }

        $failed = true === $input->getOption('strict') && [] !== $dump->warnings;

        if (true === $input->getOption('check')) {
            $changes = $this->writer->getChanges($this->outputDirectory, $dump->files, $this->formShapeDumper->getExtension());
            if ([] !== $changes) {
                $io->listing($changes);
                $io->error(sprintf('%d form file(s) out of date in "%s": run app-js:dump-forms.', count($changes), $this->outputDirectory));

                return Command::FAILURE;
            }
        } else {
            $changes = $this->writer->write($this->outputDirectory, $dump->files, $this->formShapeDumper->getExtension());
            if ($io->isVerbose()) {
                $io->listing($changes);
            }
        }

        if ($failed) {
            $io->error(sprintf('%d form(s) may miss fields (--strict).', count($dump->warnings)));

            return Command::FAILURE;
        }

        $io->success(sprintf('Dumped %d form shape(s), %d file(s) changed.', count($dump->files), count($changes)));

        return Command::SUCCESS;
    }
}
