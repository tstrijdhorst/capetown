<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Capetown\Runner\PluginManager\InteractiveConfigurator;

class ConfigureCommand extends Command {
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name=null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('plugins:configure')
			 ->setDescription('Scans for config files and interactively asks the user to provide values');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$questionHelper = $this->getHelper('question');
		
		$wantsToRemoveOldConfig = $questionHelper->ask(
			$input,
			$output,
			new ConfirmationQuestion('Would you like to remove leftover configuration files if any? (y/N): ', false)
		);
		
		if ($wantsToRemoveOldConfig) {
			$this->pluginManager->removeOldPluginConfigFiles();
			$output->writeln('Any leftover configuration files have been removed');
		}
		
		$configFilePaths = $this->pluginManager->getPluginConfigFilePaths();
		foreach ($configFilePaths as $pluginName => $configFilePath) {
			$wantsToConfigurePlugin = $questionHelper->ask(
				$input,
				$output,
				new ConfirmationQuestion('Would you like to configure plugin: '.$pluginName.'? (y/N): ', false)
			);
			
			if ($wantsToConfigurePlugin) {
				InteractiveConfigurator::askForConfigValues($configFilePath, $pluginName, $input, $output, $questionHelper);
			}
		}
	}
}