<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;

class PluginManager {
	public function installPlugins(array $pluginRequirements): void {
		$composerPath         = Constants::BASEDIR.'composer.json';
		$composerFileOriginal = file_get_contents($composerPath);
		$composerArray        = json_decode($composerFileOriginal, true);
		
		$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		
		try {
			file_put_contents($composerPath, json_encode($composerArray));
			$this->composerUpdate($pluginRequirements);
		}
		catch(\Throwable $e) {
			throw $e;
		}
		finally {
			file_put_contents($composerPath, $composerFileOriginal);
		}
	}
	
	/**
	 * @param array $pluginRequirements
	 */
	private function composerUpdate(array $pluginRequirements): void {
		$commandString = getenv('COMPOSER_PATH').' update';
		
		$packageNames = array_keys($pluginRequirements);
		foreach ($packageNames as $packageName) {
			$commandString .= ' '.escapeshellarg($packageName);
		}
		
		exec($commandString, $output);
	}
}