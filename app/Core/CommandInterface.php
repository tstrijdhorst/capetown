<?php

namespace Capetown\Core;

interface CommandInterface {
	public static function createDefault(KeybaseAPIClient $keybaseAPIClient): CommandInterface;
	public static function getName(): string;
	
	/**
	 * @param Message[] $messages
	 */
	public function handleMessages(array $messages):void;
}