<?php

use Capetown\Runner\Bootstrapper;
use Capetown\Runner\PluginManager\PluginManager;
use Capetown\Runner\PluginManager\StaticCodeAnalyzer;

require_once __DIR__.'/vendor/autoload.php';
Bootstrapper::bootstrap();

if ($argc < 2 || ($argv[1] !== 'plugin' && $argv[2] !== 'install')) {
	echo 'Usage: php '.$argv[0].' plugins install '.PHP_EOL;
	exit(0);
}

echo 'Installing plugins'.PHP_EOL;
$pluginManager = new PluginManager(new StaticCodeAnalyzer());

$pluginManager->installPlugins();