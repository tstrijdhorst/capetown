<?php

namespace Capetown\Runner;

use Capetown\Core\Bot;
use Capetown\Core\CoreConfig;
use Capetown\Core\KeybaseAPIClient;
use Dotenv\Dotenv;

require_once __DIR__.'/../vendor/autoload.php';

(new class  {
	private const CONFIG_DIR = __DIR__.'/../config';
	
	public static function run(): void {
		self::importConfiguration();
		self::exportCoreConfig();
		
		$bot = new Bot(new KeybaseAPIClient(), EnabledCommands::getEnabledCommandClasses());
		$bot->run();
	}
	
	private static function importConfiguration(): void {
		$configFileNames = ['.env'];
		foreach (EnabledCommands::getEnabledCommandClasses() as $commandClass) {
			$configFileName        = $commandClass::getName().'.env';
			$commandConfigFilePath = self::CONFIG_DIR.'/'.$configFileName;
			if (is_file($commandConfigFilePath)) {
				$configFileNames[] = $configFileName;
			}
		}
		
		foreach ($configFileNames as $configFileName) {
			$dotEnv = new Dotenv(self::CONFIG_DIR, $configFileName);
			$dotEnv->load();
		}
	}
	
	private static function exportCoreConfig(): void {
		CoreConfig::$verboseMode = boolval(getenv('VERBOSE'));
	}
})::run();

