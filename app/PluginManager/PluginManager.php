<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;

class PluginManager {
	public function installPlugins(array $pluginRequirements): void {
		$composerPath         = Constants::BASEDIR.'composer.json';
		$composerFileOriginal = file_get_contents($composerPath);
		$composerArray        = json_decode($composerFileOriginal, true);
		
		$composerArray['require'] = array_merge($composerArray['require'], $pluginRequirements);
		
		file_put_contents(json_encode($composerArray), $composerPath);
		
		exec(getenv('COMPOSER_PATH').' install', $output);
		
		file_put_contents($composerFileOriginal, $composerPath);
	}
}