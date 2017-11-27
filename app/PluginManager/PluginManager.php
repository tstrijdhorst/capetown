<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;
use Capetown\Runner\EnabledCommands;
use Composer\Package\Locker;

class PluginManager {
	private const COMPOSER_PATH      = Constants::BASE_DIR.'composer.json';
	private const COMPOSER_LOCK_PATH = Constants::BASE_DIR.'composer.lock';
	private const PLUGIN_PATH        = Constants::BASE_DIR.'plugins.json';
	private const PLUGIN_LOCK_PATH   = Constants::BASE_DIR.'plugins.lock';
	private const VENDOR_PATH        = Constants::BASE_DIR.'vendor/';
	
	/** @var StaticCodeAnalyzer */
	private $staticCodeAnalyzer;
	
	public function __construct(StaticCodeAnalyzer $staticCodeAnalyzer) {
		$this->staticCodeAnalyzer = $staticCodeAnalyzer;
	}
	
	public function updatePlugins(): void {
		$pluginRequirements = $this->getPluginRequirements();
		
		$composerLockFileOriginal = file_get_contents(self::COMPOSER_LOCK_PATH);
		$composerFileOriginal     = file_get_contents(self::COMPOSER_PATH);
		
		try {
			$this->addPluginRequirementsToComposerFile($pluginRequirements, $replaceComposerRequirements=true);
			$this->addPluginLockToComposerLock();
			$this->runComposerCommand($pluginRequirements, 'update');
			$this->refreshEnabledCommandsConfig($pluginRequirements);
			$this->copyPluginConfigFiles($pluginRequirements);
			$this->createPluginLockFile($pluginRequirements);
		}
		catch (\Throwable $e) {
			throw $e;
		}
		finally {
			file_put_contents(self::COMPOSER_PATH, $composerFileOriginal);
			file_put_contents(self::COMPOSER_LOCK_PATH, $composerLockFileOriginal);
		}
	}
	
	public function installPlugins(): void {
		$pluginRequirements = $this->getPluginRequirements();
		
		$composerLockFileOriginal = file_get_contents(self::COMPOSER_LOCK_PATH);
		$composerFileOriginal     = file_get_contents(self::COMPOSER_PATH);
		
		try {
			$this->addPluginRequirementsToComposerFile($pluginRequirements);
			$this->addPluginLockToComposerLock();
			$this->runComposerCommand($pluginRequirements, 'install');
			$this->refreshEnabledCommandsConfig($pluginRequirements);
			$this->copyPluginConfigFiles($pluginRequirements);
			$this->createPluginLockFile($pluginRequirements);
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
	
	/**
	 * @param array $pluginRequirements
	 * @param bool  $replaceComposerRequirements If this is set to true, it will replace the existing composer plugin
	 *                                           requirements instead of merging them
	 * @throws \Exception
	 */
	private function addPluginRequirementsToComposerFile(array $pluginRequirements, $replaceComposerRequirements = false) {
		$composerArray = json_decode(file_get_contents(self::COMPOSER_PATH), true);
		
		if ($composerArray === null) {
			throw new \Exception('Could not read composer file');
		}
		
		if ($replaceComposerRequirements) {
			$composerArray['require'] = $pluginRequirements;
		}
		else {
			$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		}
		
		file_put_contents(self::COMPOSER_PATH, json_encode($composerArray));
	}
	
	private function runComposerCommand(array $pluginRequirements, string $command): void {
		$commandString = getenv('COMPOSER_PATH').' '.escapeshellarg($command);
		
		$packageNames = array_keys($pluginRequirements);
		foreach ($packageNames as $packageName) {
			$commandString .= ' '.escapeshellarg($packageName);
		}
		
		exec($commandString, $output);
	}
	
	private function refreshEnabledCommandsConfig(array $pluginRequirements): void {
		$enabledCommandsFQNsPre = EnabledCommands::getEnabledCommandClasses();
		
		$newCommandFQNs = $this->getNewlyInstalledCommandFQNs($pluginRequirements);
		
		$enabledCommandsFQNsPost = $enabledCommandsFQNsPre;
		foreach ($newCommandFQNs as $newCommandFQN) {
			if (in_array($newCommandFQN, $enabledCommandsFQNsPre, true) === false) {
				$enabledCommandsFQNsPost[] = $newCommandFQN;
			}
		}
		
		file_put_contents(Constants::ENABLED_COMMANDS_PATH, json_encode($enabledCommandsFQNsPost));
	}
	
	private function getNewlyInstalledCommandFQNs(array $pluginRequirements): array {
		$commandFQNs = [];
		
		$pluginNames = array_keys($pluginRequirements);
		foreach ($pluginNames as $pluginName) {
			$commandFQNs = array_merge($commandFQNs, $this->getCommandClassesFQNsFromPlugin($pluginName));
		}
		
		return $commandFQNs;
	}
	
	private function getCommandClassesFQNsFromPlugin(string $pluginName): array {
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
	
	private function getPHPFilePaths(string $pluginDirectoryPath): array {
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
				copy($pluginConfigPath, Constants::CONFIG_DIR.$configFileName);
			}
		}
	}
	
	private function createPluginLockFile(array $pluginRequirements) {
		$composerLockArray = json_decode(file_get_contents(self::COMPOSER_LOCK_PATH), true);
		
		$pluginNames = array_keys($pluginRequirements);
		
		$pluginLockArray = ['packages' => []];
		foreach ($composerLockArray['packages'] as $package) {
			if (in_array($package['name'], $pluginNames)) {
				$pluginLockArray['packages'][] = $package;
			}
		}
		
		file_put_contents(self::PLUGIN_LOCK_PATH, json_encode($pluginLockArray));
	}
	
	private function addPluginLockToComposerLock(): void {
		if (is_file(self::PLUGIN_LOCK_PATH) === false) {
			return;
		}
		
		$pluginLockArray   = json_decode(file_get_contents(self::PLUGIN_LOCK_PATH), true);
		$composerLockArray = json_decode(file_get_contents(self::COMPOSER_LOCK_PATH), true);
		$composerFile      = file_get_contents(self::COMPOSER_PATH);
		
		$composerLockArray['packages']     = array_merge($composerLockArray['packages'], $pluginLockArray['packages']);
		$composerLockArray['hash']         = md5($composerFile);
		$composerLockArray['content-hash'] = Locker::getContentHash($composerFile);
		
		file_put_contents(self::COMPOSER_LOCK_PATH, json_encode($composerLockArray));
	}
}