<?php

namespace Capetown\Plugins\Giphy;

class GiphyAPIClient {
	/**
	 * @param $searchQuery
	 * @return \SplFileObject
	 * @throws \Exception
	 */
	public function getRandomGif($searchQuery): \SplFileObject {
		$searchResultsRaw = json_decode(file_get_contents('https://api.giphy.com/v1/gifs/search?api_key=d2evT7jdFzVkllsBjRZ6yyDLO0nZulNh&q='.urlencode($searchQuery).'&limit=25&offset=0&rating=G&lang=en'), true)['data'];
		
		if (count($searchResultsRaw) === 0) {
			throw new NoSearchResultsFoundException();
		}
		
		$randomSearchResultRaw = $searchResultsRaw[rand(0, count($searchResultsRaw) - 1)];
		
		$randomGif = file_get_contents($randomSearchResultRaw['images']['original']['url']);
		if ($randomGif === false) {
			throw new \Exception('Could not download gif');
		}
		
		$fileName = \Capetown\TEMP_DIR.'/gifs/'.$randomSearchResultRaw['id'].'gif';
		file_put_contents($fileName, $randomGif);
		
		$fileObject = new \SplFileObject($fileName);
	}
}