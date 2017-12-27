<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ConfigureCommand extends Command {
	/** @var PluginManager */
	private $pluginManager;
	/** @var QuestionHelper */
	private $questionHelper;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name=null);
		$this->pluginManager = $pluginManager;
		$this->questionHelper = $this->getHelper('question');
	}
	
	protected function configure() {
		$this->setName('plugins:configure')
			 ->setDescription('Scans for config files and interactively asks the user to provide values');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$configFilePaths = $this->pluginManager->getPluginConfigFilePaths();
		
		foreach ($configFilePaths as $pluginName => $configFilePath) {
			$wantsToConfigurePlugin = $this->questionHelper->ask(
				$input,
				$output,
				new ConfirmationQuestion('Would you like to configure plugin: '.$pluginName, false)
			);
			
			if ($wantsToConfigurePlugin) {
				$configurator = new InteractiveConfigurator($configFilePath, $input, $output, $this->questionHelper);
				$configurator->askForConfigValues();
			}
		}
	}
}