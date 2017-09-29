<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/KeybaseAPIClient.php';
require_once __DIR__.'/GiphyAPIClient.php';

$loop = React\EventLoop\Factory::create();

$keybaseApi = new KeybaseAPIClient($loop);
$giphyApi   = new GiphyAPIClient();

$loop->addPeriodicTimer(
	3, function () use ($keybaseApi, $giphyApi) {
	foreach ($keybaseApi->getUnreadMessages() as $message) {
//		if (substr($message['body'], 0, 7) === '/giphy ') {
//			$searchQuery = substr($message['body'], 7); //@todo this might be off by 1 at the start
//			$randomGif   = $giphyApi->getRandomGif($searchQuery);
//			$keybaseApi->uploadFile($message['channel'], $randomGif);
//		}
		var_dump($message);
	}
}
);

$loop->run();