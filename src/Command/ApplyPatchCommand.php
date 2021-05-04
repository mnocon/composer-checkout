<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use MarekNocon\ComposerCheckout\PullRequest\GithubPullRequestData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplyPatchCommand extends BaseCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('apply-patch');
        $this->setHelp(
            'Downloads a patch from given Pull Request and applies it to the existing package in vendor directory'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pullRequestUrls = $this->validateInput($input->getArgument('pullRequestUrls'));

        $io = new SymfonyStyle($input, $output);

        foreach ($pullRequestUrls as $pullRequestUrl) {
            $pullRequestData = GithubPullRequestData::fromUrl($pullRequestUrl);
            $patchFileName = $this->downloadPatch($pullRequestData, $io);
            $this->applyPatch($pullRequestData, $patchFileName, $io);
        }

        $this->executePostInstallCommands($output);

        return 0;
    }

    private function downloadPatch(GithubPullRequestData $pullRequestData, SymfonyStyle $io): string
    {
        $diff = $this->getDownloader()
            ->get(
                sprintf(
                'https://api.github.com/repos/%s/%s/pulls/%s',
                $pullRequestData->owner,
                $pullRequestData->package,
                $pullRequestData->id
                ),
                ['http' => ['header' => ['Accept: application/vnd.github.VERSION.diff']]]
            )
            ->getBody();

        $fileName = sprintf('patch_%s-%s-%s', $pullRequestData->owner, $pullRequestData->package, $pullRequestData->id);
        file_put_contents($fileName, $diff);
        $io->success(sprintf('Downloaded patch: %s', $fileName));

        return $fileName;
    }

    private function applyPatch(GithubPullRequestData $pullRequestData, string $patchFileName, SymfonyStyle $io): void
    {
        $output = [];
        $result_code = 0;

        $directory = sprintf('vendor/%s/%s', $pullRequestData->owner, $pullRequestData->package);

        if (!@is_dir($directory)) {
            $directory = $io->ask(
                sprintf('The "%s" directory does not exist. Please enter the path to the directory to patch', $directory),
                $directory
            );
        }

        $command = sprintf('patch -d %s -i ../../../%s -Np1', $directory, $patchFileName);

        $io->writeln(sprintf('Running command: %s', $command));

        exec($command, $output, $result_code);
        $io->writeln($output);

        if (!$this->isPatchSuccessFull($result_code, $output)) {
            $io->error('Patch failed');
            throw new \RuntimeException('Failed applying patch');
        }

        $io->success('Applied patch!');
    }

    /**
     * @param string[] $output
     */
    private function isPatchSuccessFull(int $result_code, array $output): bool
    {
        return $result_code === 0 && empty(array_filter($output, static function (string $outputLine) {
            return strpos($outputLine, 'failed') !== false;
        }));
    }
}
