<?php

namespace Capetown\Plugins\Giphy;

use Throwable;

class NoSearchResultsFoundException extends \Exception {
	public function __construct($searchQuery, $code = 0, Throwable $previous = null) {
		parent::__construct('No search results found for: '.$searchQuery, $code, $previous);
	}
}