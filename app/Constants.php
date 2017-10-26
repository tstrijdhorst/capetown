<?php

namespace Capetown\Runner;

class Constants {
	const BASE_DIR              = __DIR__.'/../';
	const APP_DIR               = self::BASE_DIR.'app/';
	const CONFIG_DIR            = self::BASE_DIR.'config/';
	const ENABLED_COMMANDS_PATH = self::CONFIG_DIR.'enabledCommands.json';
}