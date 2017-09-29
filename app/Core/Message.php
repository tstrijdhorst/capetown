<?php

namespace Capetown\Core;

class Message {
	/**
	 * @var array
	 */
	private $channel;
	/**
	 * @var string
	 */
	private $username;
	/**
	 * @var string
	 */
	private $body;
	/**
	 * @var DateTime
	 */
	private $sent_at;
	
	public function __construct(array $channel, string $username, string $body, \DateTime $sent_at) {
		$this->channel  = $channel;
		$this->username = $username;
		$this->body     = $body;
		$this->sent_at  = $sent_at;
	}
	
	/**
	 * @return array
	 */
	public function getChannel(): array {
		return $this->channel;
	}
	
	/**
	 * @param array $channel
	 * @return Message
	 */
	public function setChannel(array $channel): Message {
		$this->channel = $channel;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getUsername(): string {
		return $this->username;
	}
	
	/**
	 * @param string $username
	 * @return Message
	 */
	public function setUsername(string $username): Message {
		$this->username = $username;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getBody(): string {
		return $this->body;
	}
	
	/**
	 * @param string $body
	 * @return Message
	 */
	public function setBody(string $body): Message {
		$this->body = $body;
		return $this;
	}
	
	/**
	 * @return DateTime
	 */
	public function getSentAt(): DateTime {
		return $this->sent_at;
	}
	
	/**
	 * @param DateTime $sent_at
	 * @return Message
	 */
	public function setSentAt(DateTime $sent_at): Message {
		$this->sent_at = $sent_at;
		return $this;
	}
}