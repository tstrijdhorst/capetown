<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name = null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:update')
			 ->setDescription('Updates your plugins to the latest version according to plugins.json, and updates the plugins.lock file.')
			 ->setHelp('Updates your plugins to the latest version according to plugins.json, and updates the plugins.lock file. Automatically refreshes the enabled commands and syncs configuration');
		
		$this->addOption('no-refresh', null, InputOption::VALUE_NONE, 'Do not refresh enabled plugins', false);
		$this->addOption('no-configure', null, InputOption::VALUE_NONE, 'Do not sync plugin configuration', false);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('Updating plugins');
		
		$refreshCommands   = boolval($input->getOption('no-refresh'));
		$syncConfiguration = boolval($input->getOption('no-configure'));
		$this->pluginManager->updatePlugins($refreshCommands, $syncConfiguration);
	}
}