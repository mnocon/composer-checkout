<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Command\BaseCommand;
use Composer\Util\HttpDownloader;
use MarekNocon\ComposerCheckout\PullRequest\GithubPullRequestData;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class ApplyPatchCommand extends BaseCommand
{
    /** @var HttpDownloader */
    private $downloader;

    protected function configure(): void
    {
        $this->setName('apply-patch');
        $this->addArgument('pullRequestUrls', InputArgument::IS_ARRAY);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // public
//        $input->setArgument('pullRequestUrls', ['https://github.com/ezsystems/ezplatform-admin-ui/pull/1747']);
        $input->setArgument('pullRequestUrls', ['https://github.com/ibexa/migrations/pull/218']);
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
        $this->validateInput($input);

        $io = new SymfonyStyle($input, $output);

        $this->downloader = new HttpDownloader($this->getIO(), $this->getComposer()->getConfig());

        foreach ($input->getArgument('pullRequestUrls') as $pullRequestUrl) {
            $pullRequestData = GithubPullRequestData::fromUrl($pullRequestUrl);
            $patchFileName = $this->downloadPatch($pullRequestData, $io);
            $this->applyPatch($pullRequestData, $patchFileName, $io);
        }

        return 0;
    }

    private function validateInput(InputInterface $input)
    {
    }

    private function downloadPatch(GithubPullRequestData $pullRequestData, SymfonyStyle $io)
    {
        $diff = $this->requestGitHubApi(
            sprintf(
            'https://api.github.com/repos/%s/%s/pulls/%s',
            $pullRequestData->owner,
            $pullRequestData->package,
            $pullRequestData->id
            ),
            [
                'http' => ['header' => ['Accept: application/vnd.github.VERSION.diff']],
            ]
        );

        $filePath = sprintf('patch_%s-%s-%s', $pullRequestData->owner, $pullRequestData->package, $pullRequestData->id);

        file_put_contents($filePath, $diff);
        $io->success(sprintf("Downloaded patch: %s", $filePath));

        return $filePath;
    }

    private function applyPatch(GithubPullRequestData $pullRequestData, $patchFileName, SymfonyStyle $io): void
    {
        $output = [];
        $result_code = 0;

        if (!@is_dir(sprintf('vendor/%s/%s', $pullRequestData->owner, $pullRequestData->package))) {
            $io->error('Directory does not exist');
            throw new \RuntimeException('Dir does not exist');
        }

        $command = sprintf('patch -d vendor/%s/%s -i ../../../%s -Np1', $pullRequestData->owner, $pullRequestData->package, $patchFileName);

        exec($command, $output, $result_code);
        $io->writeln($output);

        if (!$this->isPatchSuccessFull($result_code, $output)) {
            $io->error('Patch failed');
            throw new \RuntimeException('Failed applying patch');
        }

        $io->success('Applied patch!');
    }

    private function requestGitHubApi(string $path, array $options)
    {
        return $this->downloader->get($path, $options)->getBody();
    }

    private function isPatchSuccessFull(int $result_code, array $output)
    {
        return $result_code === 0 && empty(array_filter($output, function(string $outputLine) {
            return strpos($outputLine, 'failed') !== false;
        }));
    }
}
