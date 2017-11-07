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
		$fileNames = scandir(Constants::CONFIG_DIR);
		
		foreach ($fileNames as $fileName) {
			$filePath = Constants::CONFIG_DIR.$fileName;
			if (is_file($filePath) && substr($filePath, -4) === '.env') {
				$dotEnv = new Dotenv(Constants::CONFIG_DIR, $fileName);
				$dotEnv->load();
			}
		}
	}
	
	private static function exportCoreConfig(): void {
		CoreConfig::$verboseMode = boolval(getenv('VERBOSE'));
	}
}