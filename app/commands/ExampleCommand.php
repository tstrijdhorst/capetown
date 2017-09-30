<?php

namespace Capetown\Runner\Commands;

use Capetown\Core\CommandInterface;
use Capetown\Core\KeybaseAPIClient;
use Capetown\Core\Message;

class ExampleCommand implements CommandInterface {
	/**
	 * @var KeybaseAPIClient
	 */
	private $keybaseAPIClient;
	
	public function __construct(KeybaseAPIClient $keybaseAPIClient) {
		$this->keybaseAPIClient = $keybaseAPIClient;
	}
	
	public static function createDefault(KeybaseAPIClient $keybaseAPIClient): CommandInterface 	{
		return new self($keybaseAPIClient);
	}
	
	public static function getName(): string {
		return 'example';
	}
	
	/**
	 * @param Message[] $messages
	 */
	public function handleMessages(array $messages): void {
		foreach ($messages as $message) {
			$responseBody = 'You said this to me: '.$message->getBody();
			$this->keybaseAPIClient->sendMessage($message->getChannel(), $responseBody);
		}
	}
}