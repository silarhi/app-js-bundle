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

use LogicException;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs every dump: the enums, the constants and the form shapes (when symfony/form is enabled).
 */
#[AsCommand(
    name: 'app-js:dump',
    description: 'Dump the enums, the constants and the form shapes',
)]
final class DumpCommand extends Command
{
    private const COMMANDS = ['app-js:dump-enums', 'app-js:dump-constants', 'app-js:dump-forms'];

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('check', null, InputOption::VALUE_NONE, 'Fail when a file is out of date, without writing it (-v shows the diff)')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Fail on the warnings of forms whose shape may miss fields');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication() ?? throw new LogicException('The app-js:dump command needs its console application.');

        // Every dump runs, so a check reports all the files out of date at once
        $status = Command::SUCCESS;
        foreach (self::COMMANDS as $name) {
            if (!$application->has($name)) {
                continue;
            }

            $arguments = ['command' => $name];
            if (true === $input->getOption('check')) {
                $arguments['--check'] = true;
            }

            if ('app-js:dump-forms' === $name && true === $input->getOption('strict')) {
                $arguments['--strict'] = true;
            }

            $subInput = new ArrayInput($arguments);
            $subInput->setInteractive(false);
            if (Command::SUCCESS !== $application->find($name)->run($subInput, $output)) {
                $status = Command::FAILURE;
            }
        }

        return $status;
    }
}
