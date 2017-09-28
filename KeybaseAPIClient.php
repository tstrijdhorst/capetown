<?php


use React\EventLoop\LoopInterface;

class KeybaseAPIClient {
	/**
	 * @var LoopInterface
	 */
	private $loop;
	
	public function __construct(LoopInterface $loop) {
		$this->loop = $loop;
	}
	
	/**
	 * @return array
	 */
	public function getNewMessages() : array {
		$listCommand = [
			'method' => 'list'
		];
		
		$listCommandJson = json_encode($listCommand);
		exec('keybase chat api -m '.escapeshellarg($listCommandJson), $output);
		
		var_dump($output);
	}
	
	public function sendMessage(string $channel, string $message) {
	}
	
	public function uploadFile(string $channel, $file) {
	
	}
}