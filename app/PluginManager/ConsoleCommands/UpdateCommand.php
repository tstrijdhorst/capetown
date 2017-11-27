<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name=null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:update')
			->setDescription('Update all the packages required in the composer.json and plugins.json files. On changes, creates new plugins.lock file.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('Updating plugins');
		$this->pluginManager->updatePlugins();
	}
}