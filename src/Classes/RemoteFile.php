<?php

namespace Cherrycake\Classes;

/**
 * A class that represents a file that resides locally
 */
class RemoteFile {
	public function __construct(
		/**
		 * var $name string The name of the file, including extension
		 */
		public string $name,
		/**
		 * var $baseUrl string The base URL where the file can be loaded by an HTTP client, without a trailing slash.
		 */
		public ?string $urlBase,
	) {}

	/**
	 * @return string The URL where the file can be accessed by an HTTP client
	 */
	public function getUrl(): string {
		return $this->urlBase.'/'.$this->urlName;
	}
}
