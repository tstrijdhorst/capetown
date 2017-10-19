<?php

namespace Capetown\Runner;

use Capetown\Core\Bot;
use Capetown\Core\KeybaseAPIClient;

require_once __DIR__.'/../vendor/autoload.php';

(new class  {
	public static function run(): void {
		Bootstrapper::bootstrap();
		
		$bot = new Bot(new KeybaseAPIClient(), EnabledCommands::getEnabledCommandClasses());
		$bot->run();
	}
})::run();

