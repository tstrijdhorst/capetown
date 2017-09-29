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
	 * @var \DateTime
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
	 * @return string
	 */
	public function getUsername(): string {
		return $this->username;
	}
	
	/**
	 * @return string
	 */
	public function getBody(): string {
		return $this->body;
	}
	
	/**
	 * @return \DateTime
	 */
	public function getSentAt(): \DateTime {
		return $this->sent_at;
	}
}