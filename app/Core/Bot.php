<?php

namespace Capetown\Core;

class Bot {
	/**
	 * @var KeybaseAPIClient
	 */
	private $keybaseAPIClient;
	/**
	 * @note these are not actually instances but FQNS, there is no doc syntax for this AFAIK
	 * @var CommandInterface[]
	 */
	private $enabledCommandClasses;
	
	public function __construct(KeybaseAPIClient $keybaseAPIClient, array $enabledCommands) {
		$this->keybaseAPIClient      = $keybaseAPIClient;
		$this->enabledCommandClasses = $enabledCommands;
	}
	
	/**
	 * @return CommandInterface[]
	 * @throws \Exception - if there are multiple commands with the same name
	 */
	public function getCommands(): array {
		$commands = [];
		foreach ($this->enabledCommandClasses as $commandClass) {
			if (isset($commands[$commandClass::getName()])) {
				throw new \Exception('Duplicate command name: '.$commandClass::getName());
			}
			
			$commands[$commandClass::getName()] = $commandClass::createDefault($this->keybaseAPIClient);
		}
		
		return $commands;
	}
	
	public function run(): void {
		$loop = \React\EventLoop\Factory::create();
		
		$keybaseApiClient = $this->keybaseAPIClient;
		$commands         = $this->getCommands();
		$loop->addPeriodicTimer(
			1, function () use ($keybaseApiClient, $commands) {
			$messagesUnread = $keybaseApiClient->getUnreadMessages();
			
			foreach ($commands as $command) {
				$command->handleMessages($messagesUnread);
			}
		}
		);
		
		$loop->run();
	}
}