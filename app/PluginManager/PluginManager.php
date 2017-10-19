<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Runner\Constants;

class PluginManager {
	public function installPlugins() {
		$pluginRequirements = json_decode(file_get_contents(Constants::BASEDIR, 'plugins.json'), true)['require'];
		
		$composerPath         = Constants::BASEDIR.'composer.json';
		$composerFileOriginal = file_get_contents($composerPath);
		$composerArray        = json_decode($composerFileOriginal, true);
		
		$composerArrayWithPluginRequirements = array_merge($composerArray['require'], $pluginRequirements);
		
		file_put_contents(json_encode($composerArrayWithPluginRequirements), $composerPath);
		
		exec(getenv('COMPOSER_PATH').' install', $output);
		
		file_put_contents(json_encode($composerFileOriginal), $composerPath);
	}
}