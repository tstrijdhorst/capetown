<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name = null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:install')
			 ->setDescription('Installs the plugins from the composer.lock file if present, or falls back on the composer.json.')
			 ->setHelp('Installs the plugins from the composer.lock file if present, or falls back on the composer.json.. Automatically refreshes the enabled commands and syncs configuration');;
		
		$this->addOption('no-refresh', null, InputOption::VALUE_NONE, 'Do not refresh enabled plugins');
		$this->addOption('no-configure', null, InputOption::VALUE_NONE, 'Do not sync plugin configuration');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('Installing plugins');
		
		$refreshCommands   = boolval($input->getOption('no-refresh'));
		$syncConfiguration = boolval($input->getOption('no-configure'));
		$this->pluginManager->installPlugins($refreshCommands, $syncConfiguration);
	}
}