<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApplyPatchCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('apply-patch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello from apply patch COmmand');

        return BaseCommand::SUCCESS;
    }
}
