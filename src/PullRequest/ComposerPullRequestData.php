<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\PullRequest;

class ComposerPullRequestData
{
    /** @var string */
    public $branchName;

    /** @var string */
    public $branchAlias;

    /** @var string */
    public $repositoryUrl;

    /** @var string */
    public $packageName;

    public function __construct(string $branchName, string $branchAlias, string $repositoryUrl, string $packageName)
    {
        $this->branchName = $branchName;
        $this->branchAlias = $branchAlias;
        $this->repositoryUrl = $repositoryUrl;
        $this->packageName = $packageName;
    }
}
