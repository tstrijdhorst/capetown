<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InteractiveConfigurator {
	public static function askForConfigValues(string $configFilePath, string $pluginName, InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper): void {
		$configMapDefault  = self::transformEnvFileToKeyValueMap($configFilePath);
		$configMapProvided = [];
		foreach ($configMapDefault as $key => $valueDefault) {
			if ($valueDefault == null) {
				$valueDefault = 'null';
			}
			
			$valueProvided = $questionHelper->ask(
				$input,
				$output,
				new Question($key.' = ? (default: '.$valueDefault.'): ', $valueDefault)
			);
			
			if ($valueProvided == 'null') {
				$valueProvided = null;
			}
			
			$configMapProvided[$key] = $valueProvided;
		}
		
		$writeToDisk = $questionHelper->ask(
			$input,
			$output,
			new ConfirmationQuestion('Write the configuration to disk? (y/N): ', false)
		);
		
		if ($writeToDisk) {
			$configFileName = str_replace('/', '_', $pluginName).'.env';
			$configENV      = self::transformKeyValueMapToEnvFile($configMapProvided);
			
			file_put_contents(Constants::CONFIG_DIR.$configFileName, $configENV);
			
			$output->writeln('Configuration for plugin: '.$pluginName.' successfully written');
		}
	}
	
	private static function transformEnvFileToKeyValueMap(string $configFilePath): array {
		$contents    = file_get_contents($configFilePath);
		$configLines = explode("\n", trim($contents, "\n"));
		
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