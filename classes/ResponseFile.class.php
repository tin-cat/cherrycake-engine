<?php

/**
 * ResponseFile
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
class ResponseFile extends Response {
	/**
	 * @var integer $contentType The content type of the response
	 */
	// protected $contentType = "application/octet-stream";

	private $filePath;
	private $fileName;

	function __construct($setup = false) {
		parent::__construct($setup);
		if ($setup["filePath"])
			$this->setFilePath($setup["filePath"]);

		if ($setup["fileName"])
			$this->setFileName($setup["fileName"]);

		$this->addHeader("Pragma: public");
		$this->addHeader("Expires: 0");
		$this->addHeader("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		$this->addHeader("Cache-Control: public");
		$this->addHeader("Content-Description: File Transfer");
		$this->addHeader("Content-Type: application/octet-stream");
		$this->addHeader("Content-Disposition: attachment; filename=\"".$this->fileName."\"");
		$this->addHeader("Content-Transfer-Encoding: binary");

		/*
		// For some reason, this causes browsers in some environments to not finish download the file
		if (!$fileSize = filesize(realpath(".".$this->filePath)."/".$this->fileName)) {
			echo "Can't determine file size ".$this->filePath.$this->fileName;
			return false;
		}
		$this->addHeader("Content-Length: ".$fileSize);
		*/
	}

	function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

	function setFileName($fileName) {
		$this->fileName = $fileName;
	}

	/**
	 * @return string The Payload as the client expects it
	 */
	function getPayloadForClient() {
		$handler = @fopen(realpath(".".$this->filePath)."/".$this->fileName, "r");

		while (!feof($handler)) {
			$payload .= fread($handler, 1024*8);
			flush();
			if (connection_status()!=0) {
				@fclose($handler);
				die();
			}
		}
		@fclose($handler);

		echo $payload;

		return $payload;
	}
}