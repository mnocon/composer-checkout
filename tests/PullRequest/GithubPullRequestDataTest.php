<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\PullRequest;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class GithubPullRequestDataTest extends TestCase
{
    /**
     * @dataProvider provideForTestFromUrl
     * @covers \MarekNocon\ComposerCheckout\PullRequest\GithubPullRequestData::fromUrl
     */
    public function testFromUrl(string $pullRequestUrl, GithubPullRequestData $expectedData): void
    {
        $prData = GithubPullRequestData::fromUrl($pullRequestUrl);
        Assert::assertEquals($prData, $expectedData);
    }

    public function provideForTestFromUrl(): array
    {
        return [
            ['https://github.com/ezsystems/ezplatform-admin-ui/pull/1747', new GithubPullRequestData('ezsystems', 'ezplatform-admin-ui', '1747')],
            ['https://github.com/ezsystems/ezplatform-admin-ui/pull/1747/files', new GithubPullRequestData('ezsystems', 'ezplatform-admin-ui', '1747')],
            ['https://github.com/ezsystems/ezplatform-admin-ui/pull/1747/checks', new GithubPullRequestData('ezsystems', 'ezplatform-admin-ui', '1747')],
            ['https://github.com/ezsystems/ezplatform-admin-ui/pull/1747/commits', new GithubPullRequestData('ezsystems', 'ezplatform-admin-ui', '1747')],
            ['https://github.com/ibexa/docker/pull/3', new GithubPullRequestData('ibexa', 'docker', '3')],
            ['https://github.com/ibexa/docker/pull/3/', new GithubPullRequestData('ibexa', 'docker', '3')],
        ];
    }
}
