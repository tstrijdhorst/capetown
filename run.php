<?php

use Capetown\Commands\Giphy\GiphyCommand;
use Capetown\Core\Bot;
use Capetown\Core\CommandInterface;
use Capetown\Core\KeybaseAPIClient;
use Dotenv\Dotenv;

require_once __DIR__.'/vendor/autoload.php';
define('Capetown\VERBOSE', true);

$enabledCommandClasses = [
	GiphyCommand::class
];

$configDir = __DIR__.'/config';

foreach($enabledCommandClasses as $commandClass) {
	$fileName = $commandClass::getName().'.env';
	if (is_file($configDir.'/'.$fileName)) {
		$dotEnv = new Dotenv($configDir, $commandClass::getName().'.env');
		$dotEnv->load();
	}
}

$keybaseApiClient = new KeybaseAPIClient();

$bot = new Bot($keybaseApiClient, $enabledCommandClasses);
$bot->run();
