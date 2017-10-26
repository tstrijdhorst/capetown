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
		$fileNames = scandir(Constants::CONFIGDIR);
		
		foreach ($fileNames as $fileName) {
			$filePath = Constants::CONFIGDIR.$fileName;
			if (is_file($filePath) && substr($filePath, -4) === '.env') {
				$dotEnv = new Dotenv(Constants::CONFIGDIR, $fileName);
				$dotEnv->load();
			}
		}
	}
	
	private static function exportCoreConfig(): void {
		CoreConfig::$verboseMode = boolval(getenv('VERBOSE'));
	}
}