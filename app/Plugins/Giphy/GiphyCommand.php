<?php

namespace Capetown\Plugins\Giphy;

use Capetown\Core\CommandInterface;
use Capetown\Core\KeybaseAPIClient;
use Dotenv\Dotenv;

class GiphyCommand implements CommandInterface {
	
	/**
	 * @var GiphyAPIClient
	 */
	private $giphyAPIClient;
	/**
	 * @var KeybaseAPIClient
	 */
	private $keybaseAPIClient;
	
	public function __construct(KeybaseAPIClient $keybaseAPIClient, GiphyAPIClient $giphyAPIClient) {
		$this->keybaseAPIClient = $keybaseAPIClient;
		$this->giphyAPIClient   = $giphyAPIClient;
	}
	
	public static function createDefault(KeybaseAPIClient $keybaseAPIClient): CommandInterface {
		$dotenv = new Dotenv(__DIR__);
		$dotenv->load();
		
		return new self($keybaseAPIClient, new GiphyAPIClient(getenv('GIPHY_API_KEY')));
	}
	
	public static function getName(): string {
		return 'giphy';
	}
	
	public function handleMessages(array $messages): void {
		foreach ($messages as $message) {
			if (substr($message->getBody(), 0, 7) === '/giphy ') {
				$searchQuery = substr($message->getBody(), 7);
				
				try {
					$randomGifPath = $this->giphyAPIClient->getRandomGif($searchQuery);
					$this->keybaseAPIClient->uploadAttachment($message->getChannel(), $randomGifPath, $searchQuery);
					unlink($randomGifPath);
				}
				catch (NoSearchResultsFoundException $e) {
					$this->keybaseAPIClient->sendMessage($message->getChannel(), $e->getMessage());
				}
			}
		}
	}
}