<?php

namespace Capetown\Runner;

use Capetown\Core\CommandInterface;
use Capetown\Runner\Commands\ExampleCommand;

class EnabledCommands {
	/**
	 * @return CommandInterface[]
	 */
	public static function getEnabledCommandClasses(): array {
		return [
			ExampleCommand::class,
		];
	}
}