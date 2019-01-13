<?php

/**
 * ResponseImageJpeg
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Response
 *
 * Class that represents a response to a client. Mostly used by the Output module.
 *
 * @package Cherrycake
 * @category Classes
 */
class ResponseImageJpeg extends Response {
	/**
	 * @var boolean $isProgressive Whether the jpeg is progressive or not
	 */
	private $isProgressive = false;

	function __construct($setup = false) {
		parent::__construct($setup);
		if ($setup["isProgressive"])
			$this->setProgressive($setup["isProgressive"]);
	}

	function setProgressive($isProgressive) {
		$this->isProgressive = $isProgressive;
	}

	function getContentType() {
		return $this->isProgressive ? "image/pjpeg" : "image/jpeg";

	}
}