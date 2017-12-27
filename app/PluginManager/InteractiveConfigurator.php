<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InteractiveConfigurator {
	/** @var InputInterface */
	private $input;
	/** @var OutputInterface */
	private $output;
	/** @var array */
	private $configMap;
	
	public function __construct(string $configFilePath, InputInterface $input, OutputInterface $output) {
		$this->input     = $input;
		$this->output    = $output;
		$this->configMap = $this->transformEnvFileToKeyValueMap($configFilePath);
	}
	
	private function transformEnvFileToKeyValueMap(string $configFilePath) {
		$contents    = file_get_contents($configFilePath);
		$configLines = explode("\n", $contents);
		
		$configMap = [];
		foreach ($configLines as $configLine) {
			$configLineMap = explode('=', $configLine);
			
			$key   = $configLineMap[0];
			$value = isset($configLineMap[1]) ? $configLineMap[1] : null;
			
			$configMap[$key] = $value;
		}
		
		return $configMap;
	}
}