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
			//@todo copy .env file from plugin directories to {BASEDIR}/config/{pluginName}.env (use the package name as plugin name so we do not have collisions)
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
	 * @return mixed
	 */
	private function getPluginRequirements(): mixed {
		$pluginFile         = json_decode(file_get_contents(self::PLUGIN_PATH), true);
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
		$enabledCommandsFQNSPre = json_decode(file_get_contents(self::ENABLED_COMMANDS_PATH), true);
		
		//Remove all commands that used to be loaded but are not anymore
		$enabledCommandsFQNSPost = [];
		foreach ($enabledCommandsFQNSPre as $enabledCommandFQN) {
			if (class_exists($enabledCommandFQN)) {
				$enabledCommandsFQNSPost[] = $enabledCommandFQN;
			}
		}
		
		foreach ($pluginRequirements as $pluginRequirement) {
			$pluginName          = $pluginRequirement[0];
			$pluginDirectoryPath = Constants::BASEDIR.'vendor/'.$pluginName;
			
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
			
			foreach ($phpFilePaths as $phpFilePath) {
				//@todo convert this to php 7.2 syntax
				if ($this->staticCodeAnalyzer->implementsCommandInterface($phpFilePath)) {
					$enabledCommandsFQNSPost = $this->staticCodeAnalyzer->getFQN($phpFilePath);
				}
			}
		}
		
		file_put_contents(self::ENABLED_COMMANDS_PATH, json_encode($enabledCommandsFQNSPost));
	}
}