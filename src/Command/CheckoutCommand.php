<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Command\ConfigCommand;
use Composer\Command\RequireCommand;
use MarekNocon\ComposerCheckout\PullRequest\ComposerPullRequestData;
use MarekNocon\ComposerCheckout\PullRequest\GithubPullRequestData;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckoutCommand extends BaseCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('checkout');
        $this->setHelp(
            'Adds the branch from given GitHub Pull Request as a Composer dependency.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pullRequestUrls = $this->validateInput($input->getArgument('pullRequestUrls'));

        foreach ($pullRequestUrls as $pullRequestUrl) {
            $githubPullRequestData = GithubPullRequestData::fromUrl($pullRequestUrl);
            $composerPullRequestData = $this->extractDataFromPullRequest($githubPullRequestData);
            $this->addRepository($composerPullRequestData->repositoryUrl, $output);
            $this->requireDependency($composerPullRequestData, $output);
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

    private function extractDataFromPullRequest(GithubPullRequestData $pullRequestData): ComposerPullRequestData
    {
        $pullRequestDataRequestUrl = sprintf(
            'https://api.github.com/repos/%s/%s/pulls/%s',
            $pullRequestData->owner,
            $pullRequestData->package,
            $pullRequestData->id
        );

        $pullRequestDetails = json_decode(
            $this->getDownloader()
                    ->get($pullRequestDataRequestUrl)
                    ->getBody(),
    true
        );

        $targetBranch = $pullRequestDetails['base']['ref'];
        $branchName = $pullRequestDetails['head']['ref'];
        $repositoryURL = $pullRequestDetails['head']['repo']['html_url'];

        $composerFileRequestUrl = sprintf(
            'https://api.github.com/repos/%s/%s/contents/composer.json?ref=%s',
            $pullRequestData->owner,
            $pullRequestData->package,
            $targetBranch
        );

        $composerJsonFile = json_decode(
            $this->getDownloader()
                ->get(
                    $composerFileRequestUrl,
                    ['http' => ['header' => ['Accept: application/vnd.github.v3.raw']]]
                )
                ->getBody(),
            true
        );

        $packageName = $composerJsonFile['name'];
        $branchAlias = $this->getBranchAlias($targetBranch, $composerJsonFile);

        return new ComposerPullRequestData(
            $branchName,
            $branchAlias,
            $repositoryURL,
            $packageName
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

    /**
     * @param array[] $composerJsonFile
     */
    private function getBranchAlias(string $targetBranch, array $composerJsonFile): string
    {
        $matches = [];
        if (preg_match('/^(\d+)\.(\d+)$/', $targetBranch, $matches)) {
            return sprintf('%s.%s.x-dev', $matches[1], $matches[2]);
        }

        if (!array_key_exists('branch-alias', $composerJsonFile['extra'])) {
            throw new \RuntimeException('Could not determine branch-alias');
        }

        $aliases = array_keys($composerJsonFile['extra']['branch-alias']);

        return $composerJsonFile['extra']['branch-alias'][$aliases[0]];
    }
}
