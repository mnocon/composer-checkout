<?php declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Command;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [
            new CheckoutCommand(),
            new ApplyPatchCommand(),
        ];
    }
}
