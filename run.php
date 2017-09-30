<?php

namespace Capetown;

use Capetown\Core\Bootstrap;
use Capetown\Core\Bot;
use Capetown\Core\KeybaseAPIClient;
use Capetown\Plugins\Giphy\GiphyCommand;

require_once __DIR__.'/vendor/autoload.php';
Bootstrap::bootstrap();

$enabledCommands = [
	GiphyCommand::class
];

$keybaseApiClient = new KeybaseAPIClient();

$bot = new Bot($keybaseApiClient, $enabledCommands);
$bot->run();
