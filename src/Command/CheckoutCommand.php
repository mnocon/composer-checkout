<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Command\ConfigCommand;
use Composer\Command\RequireCommand;
use MarekNocon\ComposerCheckout\PullRequest\ComposerPullRequestData;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckoutCommand extends BaseCommand
{
    public function getCommandName(): string
    {
        return 'checkout';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pullRequestUrls = $this->validateInput($input->getArgument('pullRequestUrls'));

        foreach ($pullRequestUrls as $pullRequestUrl) {
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

        if ($configCommand->run($input, $output)) {
            throw new \RuntimeException('Something wrong happened when adding repository');
        }

        $this->resetComposer();
    }

    private function extractDataFromPullRequest(string $pullRequestUrl): ComposerPullRequestData
    {
        return new ComposerPullRequestData(
            'role_update_actions',
            '1.0.x-dev',
            'https://github.com/ibexa/migrations.git',
            'ibexa/migrations'
        );
    }

    private function requireDependency(ComposerPullRequestData $pullRequestData, OutputInterface $output): void
    {
        /** @var RequireCommand */
        $requireCommand = $this->getApplication()->get('require');

        $dependencyString = sprintf('dev-%s as %s', $pullRequestData->branchName, $pullRequestData->branchAlias);
        $output->writeln(sprintf('Checking out dependency: %s:%s',
            $pullRequestData->packageName,
            $dependencyString
        ));

        $input = new ArrayInput([
            'packages' => [
                $pullRequestData->packageName,
                $dependencyString,
            ],
            '--no-scripts' => true,
        ]);

        if ($requireCommand->run($input, $output)) {
            throw new \RuntimeException('`Failed on adding dependency');
        }
    }
}
