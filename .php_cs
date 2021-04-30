<?php

$config = new EzSystems\EzPlatformCodeStyle\PhpCsFixer\Config();
$config->setFinder(
    PhpCsFixer\Finder::create()
        ->in(__DIR__ . '/src')
        ->in(__DIR__ . '/tests')
        ->files()->name('*.php')
);

return $config;