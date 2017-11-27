<?php

use Capetown\Runner\Bootstrapper;
use Capetown\Runner\PluginManager\ConsoleCommands\UpdateCommand;
use Capetown\Runner\PluginManager\PluginManager;
use Capetown\Runner\PluginManager\StaticCodeAnalyzer;
use Composer\Console\Application;

require_once __DIR__.'/vendor/autoload.php';
Bootstrapper::bootstrap();

$pluginManager = new PluginManager(new StaticCodeAnalyzer());
$application   = new Application();
$application->add(new UpdateCommand($pluginManager));
$application->run();