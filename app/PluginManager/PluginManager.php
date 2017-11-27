<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Core\CapetownException;
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
	
	public function updatePlugins(bool $refreshCommands = true, bool $syncConfiguration = true, string $specificPackageName = null): void {
		$pluginRequirements = $this->getPluginRequirements();
		
		$composerLockFileOriginal = file_get_contents(self::COMPOSER_LOCK_PATH);
		$composerFileOriginal     = file_get_contents(self::COMPOSER_PATH);
		
		try {
			$this->replacePluginRequirementsInComposerFile($pluginRequirements);
			$this->addPluginLockToComposerLock();
			$this->runComposerUpdate($pluginRequirements, $specificPackageName);
			
			if ($refreshCommands) {
				$this->refreshEnabledCommandsConfig($pluginRequirements);
			}
			
			if ($syncConfiguration) {
				$this->copyPluginConfigFiles($pluginRequirements);
			}
			
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
	
	public function installPlugins(bool $refreshCommands = true, bool $syncConfiguration = true): void {
		$pluginRequirements = $this->getPluginRequirements();
		
		$composerLockFileOriginal = file_get_contents(self::COMPOSER_LOCK_PATH);
		$composerFileOriginal     = file_get_contents(self::COMPOSER_PATH);
		
		try {
			$this->addPluginRequirementsToComposerFile($pluginRequirements);
			$this->addPluginLockToComposerLock();
			$this->runComposerInstall();
			
			if ($refreshCommands) {
				$this->refreshEnabledCommandsConfig($pluginRequirements);
			}
			
			if ($syncConfiguration) {
				$this->copyPluginConfigFiles($pluginRequirements);
			}
			
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
	
	public function refreshPlugins(): void {
		$this->refreshEnabledCommandsConfig($this->getPluginRequirements());
	}
	
	public function configure(): void {
		$this->copyPluginConfigFiles($this->getPluginRequirements());
	}
	
	public function require(string $packageName, string $version = null, bool $refreshCommands = true, bool $syncConfiguration = true): void {
		$this->addPluginRequirementToPluginsFile($packageName, $version);
		$this->updatePlugins($refreshCommands, $syncConfiguration, $packageName);
	}
	
	/**
	 * @throws \Exception
	 * @return array
	 */
	private function getPluginRequirements(): array {
		$pluginArray = $this->getPluginArray();
		
		$pluginRequirements = $pluginArray['require'];
		return $pluginRequirements;
	}
	
	private function addPluginRequirementToPluginsFile($name, $version) {
		$pluginArray = $this->getPluginArray();
		
		if ($version === null) {
			$version = '*';
		}
		
		$pluginArray['require'][$name] = $version;
		file_put_contents(self::PLUGIN_PATH, json_encode($pluginArray, JSON_PRETTY_PRINT));
	}
	
	private function replacePluginRequirementsInComposerFile($pluginRequirements) {
		$composerArray = json_decode(file_get_contents(self::COMPOSER_PATH), true);
		if ($composerArray === null) {
			throw new \Exception('Could not read composer file');
		}
		
		/**
		 * If a package from the composer.json are already in the composer.lock add it with specifically that version so it doesn't get updated.
		 * If a package from composer.json is not already in the composer.lock remove it completely so it doesn't get installed.
		 */
		$composerLockFile = file_get_contents(self::COMPOSER_LOCK_PATH);
		if ($composerLockFile !== false) {
			$composerLockArray = json_decode($composerLockFile, true);
			if ($composerArray === null) {
				throw new \Exception('Could not read composer lock file');
			}
			
			$composerLockPackageMap = [];
			foreach ($composerLockArray['packages'] as $package) {
				$composerLockPackageMap[$package['name']] = $package;
			}
			
			$composerLockPackageNames = array_keys($composerLockPackageMap);
			foreach (array_keys($composerArray['require']) as $composerPackageName) {
				if (in_array($composerPackageName, $composerLockPackageNames)) {
					$composerArray['require'][$composerPackageName] = $composerLockPackageMap[$composerPackageName]['version'];
				}
				else {
					unset($composerArray['require'][$composerPackageName]);
				}
			}
		}
		
		$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		
		file_put_contents(self::COMPOSER_PATH, json_encode($composerArray, JSON_PRETTY_PRINT));
	}
	
	private function addPluginRequirementsToComposerFile(array $pluginRequirements) {
		$composerArray = $this->getComposerArray();
		
		$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		
		file_put_contents(self::COMPOSER_PATH, json_encode($composerArray, JSON_PRETTY_PRINT));
	}
	
	private function runComposerUpdate(array $pluginRequirements, $specificPackageName = null): void {
		$commandString = getenv('COMPOSER_PATH').' update '.escapeshellarg($specificPackageName);
		
		$packageNames = array_keys($pluginRequirements);
		foreach ($packageNames as $packageName) {
			$commandString .= ' '.escapeshellarg($packageName);
		}
		
		exec($commandString, $output, $returnValue);
		
		if ($returnValue !== 0) {
			throw new CapetownException('Composer exited with errors');
		}
	}
	
	private function runComposerInstall(): void {
		$commandString = getenv('COMPOSER_PATH').' install';
		
		exec($commandString, $output, $returnValue);
		
		if ($returnValue !== 0) {
			throw new CapetownException('Composer exited with errors');
		}
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
		
		file_put_contents(Constants::ENABLED_COMMANDS_PATH, json_encode($enabledCommandsFQNsPost, JSON_PRETTY_PRINT));
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
		
		file_put_contents(self::PLUGIN_LOCK_PATH, json_encode($pluginLockArray, JSON_PRETTY_PRINT));
	}
	
	private function addPluginLockToComposerLock(): void {
		if (is_file(self::PLUGIN_LOCK_PATH) === false) {
			return;
		}
		
		$pluginLockArray   = $this->getPluginLockArray();
		
		if ($pluginLockArray === []) {
			return;
		}
		
		$composerLockArray = $this->getComposerLockArray();
		$composerFile      = file_get_contents(self::COMPOSER_PATH);
		
		$composerLockArray['packages']     = array_merge($composerLockArray['packages'], $pluginLockArray['packages']);
		$composerLockArray['hash']         = md5($composerFile);
		$composerLockArray['content-hash'] = Locker::getContentHash($composerFile);
		
		file_put_contents(self::COMPOSER_LOCK_PATH, json_encode($composerLockArray, JSON_PRETTY_PRINT));
	}
	
	/**
	 * @return array
	 * @throws \Exception
	 */
	private function getComposerArray(): array {
		$composerArray = json_decode(file_get_contents(self::COMPOSER_PATH), true);
		if ($composerArray === null) {
			throw new \Exception('Could not read composer file');
		}
		
		return $composerArray;
	}
	
	/**
	 * @return array
	 * @throws \Exception
	 */
	private function getComposerLockArray(): array {
		$composerLockArray = json_decode(file_get_contents(self::COMPOSER_LOCK_PATH), true);
		
		if ($composerLockArray === null) {
			throw new \Exception('Could not read composer lock file');
		}
		
		return $composerLockArray;
	}
	
	/**
	 * @return array
	 * @throws \Exception
	 */
	private function getPluginArray() {
		if (is_file(self::PLUGIN_PATH) === false) {
			return [];
		}
		
		$pluginArray = json_decode(file_get_contents(self::PLUGIN_PATH), true);
		
		if ($pluginArray === null) {
			throw new \Exception('Could not read plugins file');
		}
		
		return $pluginArray;
	}
	
	/**
	 * @return array
	 * @throws \Exception
	 */
	private function getPluginLockArray(): array {
		if (is_file(self::PLUGIN_LOCK_PATH) === false) {
			return [];
		}
		
		$pluginLockArray = json_decode(file_get_contents(self::PLUGIN_LOCK_PATH), true);
		
		if ($pluginLockArray === null) {
			throw new \Exception('Could not read plugins lock file');
		}
		
		return $pluginLockArray;
	}
}