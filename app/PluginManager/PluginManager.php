<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;

class PluginManager {
	const ENABLED_COMMANDS_PATH = Constants::CONFIGDIR.'enabledCommmands.json';
	const COMPOSER_PATH         = Constants::BASEDIR.'composer.json';
	const PLUGIN_PATH           = Constants::BASEDIR.'plugins.json';
	
	/** @var StaticCodeAnalyzer */
	private $staticCodeAnalyzer;
	
	public function __construct(StaticCodeAnalyzer $staticCodeAnalyzer) {
		$this->staticCodeAnalyzer = $staticCodeAnalyzer;
	}
	
	public function installPlugins(): void {
		$pluginRequirements = $this->getPluginRequirements();
		
		$composerFileOriginal = file_get_contents(self::COMPOSER_PATH);
		$composerArray        = json_decode($composerFileOriginal, true);
		
		$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		
		try {
			file_put_contents(self::COMPOSER_PATH, json_encode($composerArray));
			$this->composerUpdate($pluginRequirements);
			$this->refreshEnabledCommandsConfig($pluginRequirements);
			$this->copyPluginConfigFiles($pluginRequirements);
			//@todo refactor this in run.php and just include all .env files in {BASEDIR}/config
		}
		catch (\Throwable $e) {
			throw $e;
		}
		finally {
			file_put_contents(self::COMPOSER_PATH, $composerFileOriginal);
		}
	}
	
	/**
	 * @throws \Exception
	 * @return array
	 */
	private function getPluginRequirements(): array {
		$pluginFile = json_decode(file_get_contents(self::PLUGIN_PATH), true);
		
		if ($pluginFile === null) {
			throw new \Exception('Could not read plugins file');
		}
		
		$pluginRequirements = $pluginFile['require'];
		return $pluginRequirements;
	}
	
	private function composerUpdate(array $pluginRequirements): void {
		$commandString = getenv('COMPOSER_PATH').' update';
		
		$packageNames = array_keys($pluginRequirements);
		foreach ($packageNames as $packageName) {
			$commandString .= ' '.escapeshellarg($packageName);
		}
		
		exec($commandString, $output);
	}
	
	private function refreshEnabledCommandsConfig(array $pluginRequirements): void {
		$enabledCommandsFQNsPre = json_decode(file_get_contents(self::ENABLED_COMMANDS_PATH), true);
		
		$enabledCommandsFQNsPost = $this->getLoadedClassesThatStillExist($enabledCommandsFQNsPre);
		$enabledCommandsFQNsPost = array_merge($enabledCommandsFQNsPost, $this->getNewlyInstalledCommandFQNs($pluginRequirements));
		
		file_put_contents(self::ENABLED_COMMANDS_PATH, json_encode($enabledCommandsFQNsPost));
	}
	
	/**
	 * Remove all commands that used to be loaded but are not anymore
	 *
	 * @param array $enabledCommandsFQNsPre
	 * @return array
	 */
	private function getLoadedClassesThatStillExist(array $enabledCommandsFQNsPre): array {
		$enabledCommandsFQNs = [];
		foreach ($enabledCommandsFQNsPre as $enabledCommandFQN) {
			if (class_exists($enabledCommandFQN)) {
				$enabledCommandsFQNs[] = $enabledCommandFQN;
			}
		}
		return $enabledCommandsFQNs;
	}
	
	private function getNewlyInstalledCommandFQNs(array $pluginRequirements): array {
		$commandFQNs = [];
		foreach ($pluginRequirements as $pluginRequirement) {
			$pluginName  = $pluginRequirement[0];
			$commandFQNs = array_merge($commandFQNs, $this->getCommandClassesFQNsFromPlugin($pluginName));
		}
		
		return $commandFQNs;
	}
	
	/**
	 * @param $pluginName
	 * @return string
	 */
	private function getCommandClassesFQNsFromPlugin($pluginName): string {
		$pluginDirectoryPath = Constants::BASEDIR.'vendor/'.$pluginName.'/';
		
		$commandFQNs  = [];
		$phpFilePaths = $this->getPHPFilePaths($pluginDirectoryPath);
		
		foreach ($phpFilePaths as $phpFilePath) {
			//@todo convert this to php 7.2 syntax
			if ($this->staticCodeAnalyzer->implementsCommandInterface($phpFilePath)) {
				$commandFQNs = $this->staticCodeAnalyzer->getFQN($phpFilePath);
			}
		}
		return $commandFQNs;
	}
	
	private function getPHPFilePaths($pluginDirectoryPath): array {
		$phpFilePaths   = [];
		$directoryPaths = [$pluginDirectoryPath];
		do {
			$files = scandir(array_pop($directoryPaths));
			
			foreach ($files as $file) {
				if (is_dir($file)) {
					$directoryPaths[] = realpath($file);
					continue;
				}
				
				if (substr($file, -4) === '.php') {
					$phpFilePaths[] = realpath($file);
				}
			}
		} while (count($directoryPaths) > 1);
		
		return $phpFilePaths;
	}
	
	private function copyPluginConfigFiles(array $pluginRequirements):void {
		foreach ($pluginRequirements as $pluginRequirement) {
			$pluginName = $pluginRequirement[0];
			
			$pluginConfigPath = Constants::BASEDIR.'vendor/'.$pluginName.'/.env';
			if (file_exists($pluginConfigPath)) {
				$configFileName = str_replace('/','_', $pluginName).'.env';
				copy($pluginConfigPath, Constants::CONFIGDIR.$configFileName);
			}
		}
	}
}