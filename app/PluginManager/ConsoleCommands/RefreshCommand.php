<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name=null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:refresh')
			 ->setDescription('Enable new plugins, remove plugins that do not exist anymore.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->pluginManager->refreshPlugins();
		$output->writeln('Plugins refreshed');
	}
}