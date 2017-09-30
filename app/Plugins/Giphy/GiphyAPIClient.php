<?php

namespace Capetown\Plugins\Giphy;

class GiphyAPIClient {
	private const DOWNLOADDIR = __DIR__.'/temp/gifs/';
	/**
	 * @var string
	 */
	private $apiKey;
	
	public function __construct(string $apiKey) {
		$this->apiKey = $apiKey;
	}
	
	/**
	 * @throws \Exception
	 */
	public function getRandomGif($searchQuery): string {
		$searchURL        = 'https://api.giphy.com/v1/gifs/search?api_key='.$this->apiKey.'&q='.urlencode($searchQuery).'&limit=25&offset=0&rating=G&lang=en';
		$searchResultsRaw = json_decode(file_get_contents($searchURL), true)['data'];
		
		if (count($searchResultsRaw) === 0) {
			throw new NoSearchResultsFoundException($searchQuery);
		}
		
		$randomSearchResultRaw = $searchResultsRaw[rand(0, count($searchResultsRaw) - 1)];
		
		$randomGif = file_get_contents($randomSearchResultRaw['images']['original']['url']);
		if ($randomGif === false) {
			throw new \Exception('Could not download gif');
		}
		
		if (file_exists(self::DOWNLOADDIR) === false) {
			mkdir(self::DOWNLOADDIR);
		}
		
		$filePath = self::DOWNLOADDIR.$randomSearchResultRaw['id'].'.gif';
		file_put_contents($filePath, $randomGif);
		
		return $filePath;
	}
}