<?php

use Capetown\Runner\Bootstrapper;
use Capetown\Runner\PluginManager\ConsoleCommands\ConfigureCommand;
use Capetown\Runner\PluginManager\ConsoleCommands\InitCommand;
use Capetown\Runner\PluginManager\ConsoleCommands\InstallCommand;
use Capetown\Runner\PluginManager\ConsoleCommands\RefreshCommand;
use Capetown\Runner\PluginManager\ConsoleCommands\RequireCommand;
use Capetown\Runner\PluginManager\ConsoleCommands\UpdateCommand;
use Capetown\Runner\PluginManager\PluginManager;
use Capetown\Runner\PluginManager\StaticCodeAnalyzer;
use Symfony\Component\Console\Application;

require_once __DIR__.'/vendor/autoload.php';
Bootstrapper::bootstrap();

$pluginManager = new PluginManager(new StaticCodeAnalyzer());
$application   = new Application();
$application->add(new ConfigureCommand($pluginManager));
$application->add(new InitCommand($pluginManager));
$application->add(new InstallCommand($pluginManager));
$application->add(new RefreshCommand($pluginManager));
$application->add(new RequireCommand($pluginManager));
$application->add(new UpdateCommand($pluginManager));
$application->run();