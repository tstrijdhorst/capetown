<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name=null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:install')
			 ->setDescription('Install all the packages required in the composer.lock and plugins.lock files. If there is no composer.lock or plugin.lock it will use the entries from the .json files.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('Installing plugins');
		$this->pluginManager->installPlugins();
	}
}