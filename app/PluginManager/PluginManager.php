<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;
use Capetown\Runner\EnabledCommands;

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
	
	public function installPlugins(): void {
		$pluginRequirements = $this->getPluginRequirements();
		
		$composerLockFileOriginal = file_get_contents(self::COMPOSER_LOCK_PATH);
		$composerFileOriginal     = file_get_contents(self::COMPOSER_PATH);
		
		try {
			$this->addPluginRequirementsToComposerFile($pluginRequirements);
			$this->addPluginLockToComposerLock();
			$this->composerUpdate($pluginRequirements);
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
	
	private function addPluginRequirementsToComposerFile($pluginRequirements) {
		$composerArray = json_decode(file_get_contents(self::COMPOSER_PATH), true);
		
		if ($composerArray === null) {
			throw new \Exception('Could not read composer file');
		}
		
		$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		file_put_contents(self::COMPOSER_PATH, json_encode($composerArray));
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
				copy($pluginConfigPath, Constants::CONFIG_DIR.$configFileName);
			}
		}
	}
	
	private function createPluginLockFile(array $pluginRequirements) {
		$composerLockArray = json_decode(file_get_contents(self::COMPOSER_LOCK_PATH));
		
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
		
		$pluginLockArray   = json_decode(file_get_contents(self::PLUGIN_LOCK_PATH));
		$composerLockArray = json_decode(file_get_contents(self::COMPOSER_LOCK_PATH));
		$composerFile      = file_get_contents(self::COMPOSER_PATH);
		$composerArray     = json_decode($composerFile);
		
		$composerLockArray['packages']     = array_merge($composerLockArray['packages'], $pluginLockArray['packages']);
		$composerLockArray['hash']         = md5($composerFile);
		$composerLockArray['content-hash'] = $this->getContentHash($composerArray);
		
		file_put_contents(self::COMPOSER_LOCK_PATH, json_encode($composerLockArray));
	}
	
	/**
	 * @todo include composer as a dependency and use that function since it's public static
	 * Returns the md5 hash of the sorted content of the composer file.
	 *
	 * @param array $composerArray The contents of the composer file.
	 *
	 * @return string
	 */
	private function getContentHash(array $composerArray) {
		$relevantKeys = array(
			'name',
			'version',
			'require',
			'require-dev',
			'conflict',
			'replace',
			'provide',
			'minimum-stability',
			'prefer-stable',
			'repositories',
			'extra',
		);
		
		$relevantContent = array();
		
		foreach (array_intersect($relevantKeys, array_keys($composerArray)) as $key) {
			$relevantContent[$key] = $composerArray[$key];
		}
		if (isset($composerArray['config']['platform'])) {
			$relevantContent['config']['platform'] = $composerArray['config']['platform'];
		}
		
		ksort($relevantContent);
		
		return md5(json_encode($relevantContent));
	}
}