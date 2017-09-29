<?php

namespace Capetown;

use Capetown\Core\Bootstrap;
use Capetown\Core\KeybaseAPIClient;
use Capetown\Plugins\Giphy\GiphyAPIClient;

require_once __DIR__.'/../vendor/autoload.php';

Bootstrap::bootstrap();

$loop = \React\EventLoop\Factory::create();

$keybaseApi = new KeybaseAPIClient($loop);
$giphyApi   = new GiphyAPIClient();

$loop->addPeriodicTimer(
	3, function () use ($keybaseApi, $giphyApi) {
	foreach ($keybaseApi->getUnreadMessages() as $message) {
		if (substr($message->getBody(), 0, 7) === '/giphy ') {
			$searchQuery = substr($message->getBody(), 7);
			$randomGif   = $giphyApi->getRandomGif($searchQuery);
			$keybaseApi->uploadFile($message->getChannel(), $randomGif);
		}
	}
}
);

$loop->run();