<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RequireCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name = null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:require')
			 ->setDescription('Require a new plugin')
			 ->setHelp('Require a new plugin. Automatically refreshes the enabled commands and syncs configuration');
		
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin packagist repository');
		$this->addArgument('version', InputArgument::OPTIONAL, 'The version of the plugin. If not specified, we will choose a suitable one based on the available package versions');
		
		$this->addOption('no-refresh', null, InputOption::VALUE_NONE, 'Do not refresh enabled plugins');
		$this->addOption('no-configure', null, InputOption::VALUE_NONE, 'Do not sync plugin configuration');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$name    = $input->getArgument('name');
		$version = $input->getArgument('version');
		
		$versionString = $version !== null ? $version : 'unspecified';
		$output->writeln('Requiring plugin: '.$name.' Version: '.$versionString);
		$refreshCommands   = $input->getOption('no-refresh') === false;
		$this->pluginManager->require($name, $version, $refreshCommands);
	}
}