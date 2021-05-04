<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Command\BaseCommand as ComposerBaseCommand;
use Composer\Command\RunScriptCommand;
use Composer\Util\HttpDownloader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends ComposerBaseCommand
{
    /** @var \Composer\Util\HttpDownloader */
    private $downloader;

    private const RUN_SCRIPT_COMMAND_NAME = 'run';

    abstract public function getCommandName(): string;

    protected function configure(): void
    {
        $this->setName($this->getCommandName());
        $this->addArgument('pullRequestUrls', InputArgument::IS_ARRAY);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!empty($input->getArgument('pullRequestUrls'))) {
            return;
        }

        $io = new SymfonyStyle($input, $output);

        $numberOfPullRequests = $io->ask('Please enter the number of Pull Requests', '1', static function ($number) {
            if (!is_numeric($number) || $number < 1) {
                throw new \RuntimeException('Positive integer expected.');
            }

            return (int) $number;
        });

        $pullRequestUrls = [];
        for ($i = 0; $i < $numberOfPullRequests; ++$i) {
            $pullRequestUrls[] = $io->ask('Link to Pull Request', null, static function ($answer) {
                if (!is_string($answer) || strpos($answer, 'github.com') === false) {
                    throw new \RuntimeException(
                        'Link to Pull Request on GitHub expected. Example: https://github.com/ibexa/recipes/pull/22'
                    );
                }

                return $answer;
            });
        }

        $input->setArgument('pullRequestUrls', $pullRequestUrls);
    }

    /**
     * @param string[] $pullRequestUrls
     *
     * @return string[]
     */
    protected function validateInput(array $pullRequestUrls): array
    {
        if (!empty(array_filter($pullRequestUrls, static function (string $pullRequestUrl) {
            return strpos($pullRequestUrl, 'github.com') === false;
        }))) {
            throw new \RuntimeException('One of the URLs does not look like a GitHub Pull Request urls');
        }

        return $pullRequestUrls;
    }

    protected function executePostInstallCommands(OutputInterface $output): void
    {
        /** @var RunScriptCommand */
        $runCommand = $this->getApplication()->get(self::RUN_SCRIPT_COMMAND_NAME);

        $input = new ArrayInput([
            'script' => 'post-install-cmd',
        ]);

        if ($runCommand->run($input, $output)) {
            throw new \RuntimeException('Something wrong happened when running post-install-cmd');
        }
    }

    protected function getDownloader(): HttpDownloader
    {
        if ($this->downloader) {
            return $this->downloader;
        }

        $composer = $this->getComposer();

        if ($composer === null) {
            throw new \RuntimeException('Failure initialising Composer');
        }

        $this->downloader = new HttpDownloader($this->getIO(), $composer->getConfig());

        return $this->downloader;
    }
}
