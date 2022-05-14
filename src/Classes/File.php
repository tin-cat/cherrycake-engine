<?php

namespace Cherrycake\Classes;

/**
 * A class that represents a file
 */
class File {
	public function __construct(
		/**
		 * var $name string The name of the file on disk, including extension
		 */
		private string $name,
		/**
		 * var $dir string The directory where the file resides, without a trailing slash
		 */
		private string $dir,
		/**
		 * var $nameUrl The name of the file that an HTTP client can use to load it
		 */
		private string $urlName,
		/**
		 * var $baseUrl string The base URL where the file can be loaded by an HTTP client, without a trailing slash
		 */
		private string $urlBase,
	) {}

	/**
	 * @return string The name of the file
	 */
	function getName(): string {
		return $this->name;
	}

	/**
	 * @return string The local path of the file to be accessed by the code
	 */
	function getLocalPath(): string {
		return $this->dir.'/'.$this->name;
	}

	/**
	 * @return string The URL where the file can be accessed by an HTTP client
	 */
	function getUrl(): string {
		return $this->urlBase.'/'.$this->urlName;
	}

	/**
	 * @return bool Whether the file exists
	 */
	function isExists(): bool {
		return file_exists($this->getLocalPath());
	}

	/**
	 * @return int The size of the file on disk
	 */
	function getSize(): int {
		return filesize($this->getLocalPath());
	}
}
