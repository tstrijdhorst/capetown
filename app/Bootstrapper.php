<?php

namespace Capetown\Runner;

use Capetown\Core\CoreConfig;
use Dotenv\Dotenv;

require_once __DIR__.'/../vendor/autoload.php';

class Bootstrapper {
	public static function bootstrap() {
		self::importConfiguration();
		self::exportCoreConfig();
	}
	
	private static function importConfiguration(): void {
		$configFileNames = ['.env'];
		foreach (EnabledCommands::getEnabledCommandClasses() as $commandClass) {
			$configFileName        = $commandClass::getName().'.env';
			$commandConfigFilePath = Constants::CONFIGDIR.'/'.$configFileName;
			if (is_file($commandConfigFilePath)) {
				$configFileNames[] = $configFileName;
			}
		}
		
		foreach ($configFileNames as $configFileName) {
			$dotEnv = new Dotenv(Constants::CONFIGDIR, $configFileName);
			$dotEnv->load();
		}
	}
	
	private static function exportCoreConfig(): void {
		CoreConfig::$verboseMode = boolval(getenv('VERBOSE'));
	}
}