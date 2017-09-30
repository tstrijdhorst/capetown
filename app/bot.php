<?php

namespace Capetown;

use Capetown\Core\Bootstrap;
use Capetown\Core\KeybaseAPIClient;
use Capetown\Plugins\Giphy\GiphyAPIClient;
use Capetown\Plugins\Giphy\NoSearchResultsFoundException;

require_once __DIR__.'/../vendor/autoload.php';

Bootstrap::bootstrap();

$loop = \React\EventLoop\Factory::create();

$keybaseApi = new KeybaseAPIClient($loop);
$giphyApi   = new GiphyAPIClient();

$loop->addPeriodicTimer(
	1, function () use ($keybaseApi, $giphyApi) {
	$messagesUnread = $keybaseApi->getUnreadMessages();
	foreach ($messagesUnread as $message) {
		if (substr($message->getBody(), 0, 7) === '/giphy ') {
			$searchQuery = substr($message->getBody(), 7);
			
			try {
				$randomGifPath = $giphyApi->getRandomGif($searchQuery);
				$keybaseApi->uploadAttachment($message->getChannel(), $randomGifPath, $searchQuery);
				unlink($randomGifPath);
			}
			catch (NoSearchResultsFoundException $e) {
				$keybaseApi->sendMessage($message->getChannel(), $e->getMessage());
			}
		}
	}
}
);

$loop->run();