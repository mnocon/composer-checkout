<?php
declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use MarekNocon\ComposerCheckout\Command\CommandProvider;

class ComposerCheckoutPlugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        if ($io->isVerbose()) {
            $io->write('ComposerCheckout plugin activated.');
        }
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        if ($io->isVerbose()) {
            $io->write('ComposerCheckout plugin deactivated.');
        }
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        if ($io->isVerbose()) {
            $io->write('ComposerCheckout plugin uninstalled.');
        }
    }

    /**
     * @return string[]
     */
    public function getCapabilities(): array
    {
        return [
            ComposerCommandProvider::class => CommandProvider::class,
        ];
    }
}
