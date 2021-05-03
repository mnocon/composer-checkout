<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Command\BaseCommand;
use Composer\Command\ConfigCommand;
use Composer\Command\RequireCommand;
use Composer\Command\RunScriptCommand;
use MarekNocon\ComposerCheckout\PullRequest\ComposerPullRequestData;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckoutCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('checkout');
        $this->addArgument('pullRequestUrls', InputArgument::IS_ARRAY);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        //TMP
        $input->setArgument('pullRequestUrls', ['https://github.com/mnocon/ezplatform-page-builder/pull/26']);

        return;

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
                if (!is_string($answer) || strpos($answer, 'github.com') !== false) {
                    throw new \RuntimeException(
                        'Link to Pull Request on GitHub expected. Example: https://github.com/ibexa/recipes/pull/22'
                    );
                }

                return $answer;
            });
        }

        $input->setArgument('pullRequestUrls', $pullRequestUrls);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($input->getArgument('pullRequestUrls') as $pullRequestUrl) {
            $composerPullRequestData = $this->extractDataFromPullRequest($pullRequestUrl);
            $this->addRepository($composerPullRequestData->repositoryUrl, $output);
            $this->requireDependency(
                $composerPullRequestData->packageName,
                $composerPullRequestData->branchName,
                $composerPullRequestData->branchAlias,
                $output);
        }

        $this->executePostInstallCommands($output);

        return 0;
    }

    private function addRepository(string $repositoryUrl, OutputInterface $output): void
    {
        /** @var ConfigCommand $configCommand */
        $configCommand = $this->getApplication()->get('config');

        $input = new ArrayInput([
            'setting-key' => uniqid('repositories.', false),
            'setting-value' => ['vcs', $repositoryUrl],
        ]);

        $configCommand->run($input, $output);
        $this->resetComposer();
    }

    private function extractDataFromPullRequest($pullRequestUrl): ComposerPullRequestData
    {
        return new ComposerPullRequestData(
            'role_update_actions',
            '1.0.x-dev',
            'https://github.com/ibexa/migrations.git',
            'ibexa/migrations'
        );

        // failing public
//        return new ComposerPullRequestData(
//            'ez-dc-user',
//            '3.0.x-dev',
//            'https://github.com/ezsystems/ezplatform-admin-ui.git',
//            'ezsystems/ezplatform-admin-ui',
//        );
    }

    private function requireDependency($packageName, $branchName, $branchAlias, OutputInterface $output): void
    {
        /** @var RequireCommand */
        $requireCommand = $this->getApplication()->get('require');

        $dependencyString = sprintf('dev-%s as %s', $branchName, $branchAlias);
        $output->writeln(sprintf('Checking out dependency %s:%s...', $packageName, $dependencyString));

        $input = new ArrayInput([
            'packages' => [
                $packageName,
                $dependencyString,
            ],
            '--no-scripts' => true,
        ]);

        if ($requireCommand->run($input, $output)) {
            throw new \RuntimeException('`Failed on adding dependency');
        }

        $output->writeln('Done ✅');
    }

    private function executePostInstallCommands(OutputInterface $output)
    {
        /** @var RunScriptCommand */
        $runCommand = $this->getApplication()->get('run');

        $input = new ArrayInput([
            'script' => 'post-install-cmd'
        ]);

        $runCommand->run($input, $output);
    }
}
