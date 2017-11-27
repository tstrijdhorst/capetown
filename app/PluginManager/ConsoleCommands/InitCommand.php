<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Capetown\Runner\Constants;
use Capetown\Runner\PluginManager\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command {
	
	/** @var PluginManager */
	private $pluginManager;
	
	public function __construct(PluginManager $pluginManager) {
		parent::__construct($name=null);
		$this->pluginManager = $pluginManager;
	}
	
	protected function configure() {
		$this->setName('init')
			 ->setDescription('Initializes the application');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('Initializing capetown configuration. Please check '.Constants::CONFIG_DIR.'.env for more information');
		copy(Constants::CONFIG_DIR.'.env.dist', Constants::CONFIG_DIR.'.env');
	}
}