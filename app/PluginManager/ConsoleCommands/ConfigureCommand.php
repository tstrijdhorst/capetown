<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name=null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:configure')
			 ->setDescription('Copies any .env config files from the plugin directory to config/<pluginName>.env');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->pluginManager->configure();
		$output->writeln('Plugin configuration synced');
	}
}