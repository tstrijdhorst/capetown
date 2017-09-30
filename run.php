<?php

use Capetown\Core\Bot;
use Capetown\Core\KeybaseAPIClient;

require_once __DIR__.'/vendor/autoload.php';
define('Capetown\VERBOSE', true);

$enabledCommands = [
];

$keybaseApiClient = new KeybaseAPIClient();

$bot = new Bot($keybaseApiClient, $enabledCommands);
$bot->run();
