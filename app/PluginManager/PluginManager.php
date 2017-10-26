<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Core\CommandInterface;
use Capetown\Runner\Constants;

class PluginManager {
	const ENABLED_COMMANDS_PATH = Constants::CONFIGDIR.'enabledCommmands.json';
	const COMPOSER_PATH         = Constants::BASEDIR.'composer.json';
	
	const PLUGIN_PATH = Constants::BASEDIR.'plugins.json';
	
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
				if ($this->implementsCommandInterface($phpFilePath)) {
					$enabledCommandsFQNSPost = $this->getFQNFromStaticFile($phpFilePath);
				}
			}
		}
		
		file_put_contents(self::ENABLED_COMMANDS_PATH, json_encode($enabledCommandsFQNSPost));
	}
	
	private function getFQNFromStaticFile(string $phpFilePath): string {
		$contents = file_get_contents($phpFilePath);
		
		$namespace  = '';
		$className  = '';
		
		//Go through each token and evaluate it as necessary
		$tokens = token_get_all($contents);
		for ($i = 0; $i < count($tokens); $i++) {
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
				//Current token is `namespace` the actual namespace starts at the next one
				while ($tokens[++$i] !== ';') {
					//Add all the namespace tokens until we hit a ;
					if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
						$namespace .= $tokens[$i][1];
					}
				}
				
				continue;
			}
			
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
				while ($tokens[++$i] !== '{') {
					if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
						$className .= $tokens[$i][1];
						break; //Classname is always one word
					}
				}
			}
		}
		
		return $namespace !== '' ? $namespace.'\\'.$className : $className;
	}
	
	public function implementsCommandInterface($phpFilePath) {
		$contents = file_get_contents($phpFilePath);
		
		$interfaces = [];
		
		$includesInterface = false;
		$alias             = 'CommandInterface';
		//Go through each token and evaluate it as necessary
		$tokens     = token_get_all($contents);
		$tokenCount = count($tokens);
		for ($i = 0; $i < $tokenCount; $i++) {
			//Check if the FQN of our command interface is used in the code
			if ($includesInterface === false && is_array($tokens[$i]) && $tokens[$i][0] === T_USE) {
				$usedFQN = '';
				//Current token is `namespace` the actual namespace starts at the next one
				while ($tokens[++$i] !== ';') {
					//Add all the namespace tokens until we hit a ;
					if (is_array($tokens[$i])) {
						$token = $tokens[$i][0];
						if (in_array($token, [T_STRING, T_NS_SEPARATOR])) {
							$usedFQN .= $tokens[$i][1];
						}
						
						if ($tokens[$i][0] === T_AS) {
							$i     += 2; //next token is a whitespace, after that it's the alias
							$alias = $tokens[$i][1];
						}
					}
				}
				$includesInterface = ($usedFQN === CommandInterface::class);
				continue;
			}
			
			//Get a list of all implemented interfaces
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_IMPLEMENTS) {
				while ($tokens[++$i] !== '{') {
					if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
						$interfaces[] = $tokens[$i][1];
					}
				}
				continue;
			}
		}
		
		if (in_array(CommandInterface::class, $interfaces, true)) {
			return true;
		}
		
		if ($includesInterface && in_array($alias, $interfaces, true)) {
			return true;
		}
		
		return false;
	}
}