<?php

namespace Capetown\Core;

class Bootstrap {
	public static function bootstrap() {
		self::arrangeEnvironment();
	}
	
	private static function arrangeEnvironment(): void {
		define('Capetown\ROOT_DIR', realpath(__DIR__.'/../../').'/');
		define('Capetown\APP_DIR', \Capetown\ROOT_DIR.'app/');
		define('Capetown\TEMP_DIR', \Capetown\ROOT_DIR.'temp/');
	}
}