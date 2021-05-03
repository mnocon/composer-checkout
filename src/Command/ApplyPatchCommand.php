<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Command\BaseCommand;
use Github\Client;
use MarekNocon\ComposerCheckout\PullRequest\GithubPullRequestData;
use MarekNocon\ComposerCheckout\Helper\ComposerHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplyPatchCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('apply-patch');
        $this->addArgument('pullRequestUrls', InputArgument::IS_ARRAY);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $input->setArgument('pullRequestUrls', ['https://github.com/ezsystems/ezplatform-admin-ui/pull/1747']);
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

        foreach ($input->getArgument('pullRequestUrls') as $pullRequestUrl) {
            $pullRequestData = GithubPullRequestData::fromUrl($pullRequestUrl);
            $patchFilePath = $this->downloadPatch($pullRequestData, $io);
            $this->applyPatch($patchFilePath);
        }

        return 0;
    }

    private function validateInput(InputInterface $input)
    {
    }

    private function downloadPatch(GithubPullRequestData $pullRequestData, SymfonyStyle $io)
    {
        return "test";
    }

    private function applyPatch($patchFilePath)
    {

    }
}
