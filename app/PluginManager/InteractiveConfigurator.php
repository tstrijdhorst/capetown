<?php

namespace Capetown\Runner\PluginManager\ConsoleCommands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InteractiveConfigurator {
	public static function askForConfigValues(string $configFilePath, InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper): void {
		$output->writeln('Configuring '.$configFilePath);
		
		$configMapDefault  = self::transformEnvFileToKeyValueMap($configFilePath);
		$configMapProvided = [];
		foreach ($configMapDefault as $key => $valueDefault) {
			$valueProvided = $questionHelper->ask(
				$input,
				$output,
				new Question($key, (string)$valueDefault)
			);
			
			$configMapProvided[$key] = $valueProvided;
		}
		
		$writeToDisk = $questionHelper->ask(
			$input,
			$output,
			new ConfirmationQuestion('Write the configuration to disk?', false)
		);
		
		if ($writeToDisk) {
			file_put_contents($configFilePath, self::transformKeyValueMapToEnvFile($configMapProvided));
		}
	}
	
	private static function transformEnvFileToKeyValueMap(string $configFilePath): array {
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
	
	private static function transformKeyValueMapToEnvFile(array $configMap): string {
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