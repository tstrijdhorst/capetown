<?php

use Capetown\Core\CommandInterface;

class EnabledCommands {
	/**
	 * @return CommandInterface[]
	 */
	public static function getEnabledCommandClasses(): array {
		return [
			//Add your Command::class here
		];
	}
}