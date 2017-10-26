<?php

namespace Capetown\Runner;

use Capetown\Core\CommandInterface;

class EnabledCommands {
	/**
	 * @return CommandInterface[]
	 * @throws \Exception
	 */
	public static function getEnabledCommandClasses(): array {
		if (!is_file(Constants::ENABLED_COMMANDS_PATH)) {
			return [];
		}
		
		$enabledCommandsFQNsPre = json_decode(file_get_contents(Constants::ENABLED_COMMANDS_PATH), true);
		
		if ($enabledCommandsFQNsPre === null) {
			throw new \Exception('Could not read enabled commands config file');
		}
		return $enabledCommandsFQNsPre;
	}
}