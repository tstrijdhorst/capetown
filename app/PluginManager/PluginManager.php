<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;

class PluginManager {
	const ENABLED_COMMANDS_PATH = Constants::CONFIGDIR.'enabledCommmands.json';
	const COMPOSER_PATH = Constants::BASEDIR.'composer.json';
	
	const PLUGIN_PATH = Constants::BASEDIR.'plugins.json';
	
	public function installPlugins(array $pluginRequirements): void {
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
		catch(\Throwable $e) {
			throw $e;
		}
		finally {
			file_put_contents(self::COMPOSER_PATH, $composerFileOriginal);
		}
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
			$pluginName = $pluginRequirement[0];
			//@todo get all files in plugin directory
			//@todo filter out php files
			//@todo check if they implement the CommandInterface
			//@todo if so, add the FQN to plugins.json
		}
		
		file_put_contents(self::ENABLED_COMMANDS_PATH, json_encode($enabledCommandsFQNSPost));
	}
	
	private function getClassInformationFromStaticFile(string $filePath) : array {
		$contents = file_get_contents($filePath);
		
		$nameSpace = $className = "";
		$interfaces = [];
		
		//Go through each token and evaluate it as necessary
		$tokens = token_get_all($contents);
		for ($i=0;$i<count($tokens);$i++) {
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
				//Current token is `namespace` the actual namespace starts at the next one
				while($tokens[++$i] !== ';') {
					//Add all the namespace tokens until we hit a ;
					if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
						$nameSpace .= $tokens[$i][1];
					}
				}
				
				continue;
			}
			
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
				while($tokens[++$i] !== '{') {
					if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
						$className .= $tokens[$i][1];
						break; //Classname is always one word
					}
				}
			}
			
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_IMPLEMENTS) {
				while($tokens[++$i] !== '{') {
					if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
						$interfaces[] = $tokens[$i][1];
					}
				}
				continue;
			}
		}
		
		return [$nameSpace, $className, $interfaces];
	}
	
	/**
	 * @return mixed
	 */
	private function getPluginRequirements(): mixed {
		$pluginFile         = json_decode(file_get_contents(self::PLUGIN_PATH), true);
		$pluginRequirements = $pluginFile['require'];
		return $pluginRequirements;
	}
}