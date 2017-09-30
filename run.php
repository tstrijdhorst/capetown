<?php

namespace Capetown;

use Capetown\Core\Bot;
use Capetown\Core\KeybaseAPIClient;
use Capetown\Plugins\Giphy\GiphyCommand;

require_once __DIR__.'/vendor/autoload.php';
define('Capetown\VERBOSE', true);

$enabledCommands = [
	GiphyCommand::class
];

$keybaseApiClient = new KeybaseAPIClient();

$bot = new Bot($keybaseApiClient, $enabledCommands);
$bot->run();
