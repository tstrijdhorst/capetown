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
	public function getUnreadMessages(): array {
		$listCommand = [
			'method' => 'list',
		];
		
		$listResult       = $this->doAPICommand($listCommand);
		$conversationsRaw = $listResult['conversations'];
		
		$messagesUnread = [];
		foreach ($conversationsRaw as $conversationRaw) {
			$hasUnreadMessages = $conversationRaw['unread'];
			if ($hasUnreadMessages) {
				$messagesUnread = array_merge($messagesUnread, $this->getUnreadMessagesFromChannel($conversationRaw['channel']));
			}
		}
		
		return $messagesUnread;
	}
	
	public function sendMessage(string $channel, string $message) {
	}
	
	public function uploadFile(string $channel, $file) {
	
	}
	
	/**
	 * @param $command
	 * @return array
	 */
	private function doAPICommand(array $command): array {
		exec('keybase chat api -m '.escapeshellarg(json_encode($command)), $output);
		
		echo 'RAW OUTPUT'.PHP_EOL.$output[0].PHP_EOL.'END RAW OUTPUT';
		
		$commandOutput = json_decode($output[0], true);
		return $commandOutput['result'];
	}
	
	
	private function getUnreadMessagesFromChannel(array $channel): array {
		$messagesRaw = $this->getUnreadMessagesFromChannelRaw($channel);
		
		$messagesUnread = [];
		foreach ($messagesRaw as $messageRaw) {
			$messageRaw = $messageRaw['msg'];
			if ($messageRaw['content']['type'] === 'text') {
				$messageStructUnread = [
					'channel'  => $messageRaw['channel'],
					'sent_at'  => new \DateTime('@'.$messageRaw['sent_at']),
					'username' => $messageRaw['sender']['username'],
					'body'     => $messageRaw['content']['text']['body'],
				];
				
				$messagesUnread[] = $messageStructUnread;
			}
		}
		
		return $messagesUnread;
	}
	
	private function getUnreadMessagesFromChannelRaw(array $channel) : array {
		$readUnreadMessagesCommand = [
			'method' => 'read',
			'params' => [
				'options'     => [
					'channel' => $channel,
				],
				'unread_only' => true,
				'peek'        => true,
			],
		];
		
		$unreadMessagesResult = $this->doAPICommand($readUnreadMessagesCommand);
		$messagesRaw          = $unreadMessagesResult['messages'];
		return $messagesRaw;
	}
}