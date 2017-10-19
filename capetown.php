<?php

use Capetown\Runner\PluginManager\PluginManager;

if ($argc < 2 || ($argv[1] !== 'plugin' && $argv[2] !== 'install')) {
	echo 'Usage: php '.$argv[0].' plugins install '.PHP_EOL;
	exit(0);
}

echo 'Installing plugins'.PHP_EOL;
$pluginManager = new PluginManager();
$pluginManager->installPlugins();