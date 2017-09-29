<?php

namespace Capetown\Core;

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
	 * @return Message[]
	 */
	public function getUnreadMessages(): array {
		$channels = $this->getChannelsWithUnreadMessages();
		
		$messagesUnread = [];
		foreach ($channels as $channel) {
			$messagesUnread = array_merge($messagesUnread, $this->getUnreadMessagesFromChannel($channel));
		}
		
		return $messagesUnread;
	}
	
	public function sendMessage(string $channel, string $message) {
	}
	
	public function uploadFile(array $channel, $file) {
	
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
	
	/**
	 * @param array $channel
	 * @return Message[]
	 */
	private function getUnreadMessagesFromChannel(array $channel): array {
		$messagesRaw = $this->getUnreadMessagesFromChannelRaw($channel);
		
		$messagesUnread = [];
		foreach ($messagesRaw as $messageRaw) {
			$messageRaw = $messageRaw['msg'];
			if ($messageRaw['unread'] === true && $messageRaw['content']['type'] === 'text') {
				$messagesUnread[] = new Message(
					$messageRaw['channel'],
					$messageRaw['sender']['username'],
					$messageRaw['content']['text']['body'],
					new \DateTime('@'.$messageRaw['sent_at'])
				);
			}
		}
		
		return $messagesUnread;
	}
	
	private function getUnreadMessagesFromChannelRaw(array $channel): array {
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

//		$unreadMessagesResult = $this->doAPICommand($readUnreadMessagesCommand);
		$unreadMessagesResult = json_decode(file_get_contents(__DIR__.'/../../temp/messages.json'), true)['result'];
		$messagesRaw          = $unreadMessagesResult['messages'];
		return $messagesRaw;
	}
	
	/**
	 * @return array
	 */
	private function getChannelsWithUnreadMessages(): array {
		$listCommand = [
			'method' => 'list',
		];

//		$listResult       = $this->doAPICommand($listCommand);
		$listResult       = json_decode(file_get_contents(__DIR__.'/../../temp/listWithUnread.json'), true)['result'];
		$conversationsRaw = $listResult['conversations'];
		
		$channels = [];
		foreach ($conversationsRaw as $conversationRaw) {
			$hasUnreadMessages = $conversationRaw['unread'];
			if ($hasUnreadMessages) {
				$channels[] = $conversationRaw['channel'];
			}
		}
		return $channels;
	}
}