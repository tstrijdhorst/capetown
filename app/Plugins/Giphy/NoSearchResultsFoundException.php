<?php

namespace Capetown\Plugins\Giphy;

use Throwable;

class NoSearchResultsFoundException extends \Exception {
	public function __construct($code = 0, Throwable $previous = null) {
		parent::__construct('No search results found', $code, $previous);
	}
}