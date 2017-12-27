<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InteractiveConfigurator {
	/** @var InputInterface */
	private $input;
	/** @var OutputInterface */
	private $output;
	/** @var array */
	private $configMap;
	/** @var string */
	private $configFilePath;
	/** @var QuestionHelper */
	private $questionHelper;
	
	public function __construct(string $configFilePath, InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper) {
		$this->input          = $input;
		$this->output         = $output;
		$this->configMap      = $this->transformEnvFileToKeyValueMap($configFilePath);
		$this->configFilePath = $configFilePath;
		$this->questionHelper = $questionHelper;
	}
	
	public function askForConfigValues(): void {
		$this->output->writeln('Configuring '.$this->configFilePath);
		
		$configMapProvided = [];
		foreach ($this->configMap as $key => $valueDefault) {
			$valueProvided = $this->questionHelper->ask(
				$this->input,
				$this->output,
				new Question($key, (string)$valueDefault)
			);
			
			$configMapProvided[$key] = $valueProvided;
		}
		
		$writeToDisk = $this->questionHelper->ask(
			$this->input,
			$this->output,
			new ConfirmationQuestion('Write the configuration to disk?', false)
		);
		
		if ($writeToDisk) {
			file_put_contents($this->configFilePath, $this->transformKeyValueMapToEnvFile($this->configMap));
		}
	}
	
	private function transformEnvFileToKeyValueMap(string $configFilePath): array {
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
	
	private function transformKeyValueMapToEnvFile(array $configMap): string {
		$envFile = '';
		foreach ($configMap as $key => $value) {
			$envFile = $key.'=';
			
			if ($value !== null) {
				$envFile .= $value;
			}
			
			$envFile .= "\n";
		}
		
		return $envFile;
	}
}