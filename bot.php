<?php

$loop = React\EventLoop\Factory::create();

$keybaseApi = new KeybaseAPIClient();
$giphyApi = new GiphyAPIClient();

$loop->addPeriodicTimer(1, function () use ($keybaseApi, $giphyApi) {
	foreach($keybaseApi->getNewMessages() as $message) {
		if (substr($message->body, 0, 7) === '/giphy ') {
			$searchQuery = substr($message->body, 7); //@todo this might be off by 1 at the start
			$randomGif = $giphyApi->getRandomGif($searchQuery);
			$keybaseApi->uploadFile($message->channel, $randomGif);
		}
	}
});

$loop->run();