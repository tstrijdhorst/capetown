<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;

class PluginManager {
	private const ENABLED_COMMANDS_PATH = Constants::CONFIGDIR.'enabledCommands.json';
	private const COMPOSER_PATH         = Constants::BASEDIR.'composer.json';
	private const COMPOSER_LOCK_PATH    = Constants::BASEDIR.'composer.lock';
	private const PLUGIN_PATH           = Constants::BASEDIR.'plugins.json';
	private const VENDOR_PATH           = Constants::BASEDIR.'vendor/';
	
	/** @var StaticCodeAnalyzer */
	private $staticCodeAnalyzer;
	
	public function __construct(StaticCodeAnalyzer $staticCodeAnalyzer) {
		$this->staticCodeAnalyzer = $staticCodeAnalyzer;
	}
	
	public function installPlugins(): void {
		$pluginRequirements = $this->getPluginRequirements();
		
		$composerLockFileOriginal = file_get_contents(self::COMPOSER_LOCK_PATH);
		$composerFileOriginal     = file_get_contents(self::COMPOSER_PATH);
		$composerArray            = json_decode($composerFileOriginal, true);
		
		if ($composerArray === null) {
			throw new \Exception('Could not read composer file');
		}
		
		$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		
		try {
			file_put_contents(self::COMPOSER_PATH, json_encode($composerArray));
			//@todo add entries from our plugin.lock to composer.lock
			$this->composerUpdate($pluginRequirements);
			$this->refreshEnabledCommandsConfig($pluginRequirements);
			$this->copyPluginConfigFiles($pluginRequirements);
			//@todo read the diff of the composer lock, add that to our own plugin.lock
			//@todo refactor this in run.php and just include all .env files in {BASEDIR}/config
		}
		catch (\Throwable $e) {
			throw $e;
		}
		finally {
			file_put_contents(self::COMPOSER_PATH, $composerFileOriginal);
			file_put_contents(self::COMPOSER_LOCK_PATH, $composerLockFileOriginal);
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
		$enabledCommandsFQNsPre = $this->getEnabledCommands();
		
		//@todo does this actually work since classes might already be in memory?
		$enabledCommandsFQNsPost = $this->filterDeletedCommands($enabledCommandsFQNsPre);
		$enabledCommandsFQNsPost = array_merge($enabledCommandsFQNsPost, $this->getNewlyInstalledCommandFQNs($pluginRequirements));
		
		file_put_contents(self::ENABLED_COMMANDS_PATH, json_encode($enabledCommandsFQNsPost));
	}
	
	/**
	 * Remove all commands that used to be loaded but are not anymore
	 *
	 * @param array $enabledCommandsFQNsPre
	 * @return array
	 */
	private function filterDeletedCommands(array $enabledCommandsFQNsPre): array {
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
		
		$pluginNames = array_keys($pluginRequirements);
		foreach ($pluginNames as $pluginName) {
			$commandFQNs = array_merge($commandFQNs, $this->getCommandClassesFQNsFromPlugin($pluginName));
		}
		
		return $commandFQNs;
	}
	
	private function getCommandClassesFQNsFromPlugin($pluginName): array {
		$pluginDirectoryPath = self::VENDOR_PATH.$pluginName.'/';
		
		$commandFQNs = [];
		foreach ($this->getPHPFilePaths($pluginDirectoryPath) as $phpFilePath) {
			//@todo convert this to php 7.2 syntax
			if ($this->staticCodeAnalyzer->implementsCommandInterface($phpFilePath)) {
				$commandFQNs[] = $this->staticCodeAnalyzer->getFQN($phpFilePath);
			}
		}
		return $commandFQNs;
	}
	
	private function getPHPFilePaths($pluginDirectoryPath): array {
		$phpFilePaths   = [];
		$directoryPaths = [$pluginDirectoryPath];
		do {
			$directoryPath = array_pop($directoryPaths);
			$fileNames     = scandir($directoryPath);
			
			foreach ($fileNames as $fileName) {
				$filePath = $directoryPath.$fileName;
				if (is_dir($filePath) && $fileName !== '.' && $fileName !== '..') {
					$directoryPaths[] = $filePath.'/';
					continue;
				}
				
				if (substr($fileName, -4) === '.php') {
					$phpFilePaths[] = $filePath;
				}
			}
		} while (count($directoryPaths) > 0);
		
		return $phpFilePaths;
	}
	
	private function copyPluginConfigFiles(array $pluginRequirements): void {
		$pluginNames = array_keys($pluginRequirements);
		foreach ($pluginNames as $pluginName) {
			$pluginConfigPath = self::VENDOR_PATH.$pluginName.'/.env.dist';
			if (file_exists($pluginConfigPath)) {
				$configFileName = str_replace('/', '_', $pluginName).'.env';
				copy($pluginConfigPath, Constants::CONFIGDIR.$configFileName);
			}
		}
	}
	
	/**
	 * @return mixed
	 * @throws \Exception
	 */
	private function getEnabledCommands(): array {
		if (!is_file(self::ENABLED_COMMANDS_PATH)) {
			return [];
		}
		
		$enabledCommandsFQNsPre = json_decode(file_get_contents(self::ENABLED_COMMANDS_PATH), true);
		
		if ($enabledCommandsFQNsPre === null) {
			throw new \Exception('Could not read enabled commands config file');
		}
		return $enabledCommandsFQNsPre;
	}
}