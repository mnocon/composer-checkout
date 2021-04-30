<?php
declare(strict_types=1);

namespace MarekNocon\ComposerCheckout\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ComposerCheckoutPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $io->write('Hello!');
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $io->write('Goodbye!');
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $io->write('Sorry to see you go!');
    }
}
