<?php

namespace Cherrycake\Classes;

use Exception;

/**
 * A class that represents a file
 */
class File {
	/**
	 * var $baseDir string The base directory where files of this class reside locally, without a trailing slash. For example: '/var/www/web/public/files'
	 */
	protected string $baseDir;

	/**
	 * var $baseUrl string The base URL where files of this class can be loaded by an HTTP client, without a trailing slash. For example: '/files'
	 */
	protected string $urlBase = '';

	/**
	 * var $uniqIdNumberOfCharacters The number of characters in the generated unique ids for images. Must be at least 3
	 */
	protected int $uniqIdNumberOfCharacters = 16;

	public function __construct(
		/**
		 * var string $originalName The original name of the file, including extension
		 */
		public string $originalName,
		/**
		 * var string $id The unique identified of the file
		 */
		public string $id,
	) {}

	/**
	 * @return string A random unique identifier to identify files
	 */
	private function buildUniqueFileIdentifier(): string {
		return bin2hex(random_bytes($this->uniqIdNumberOfCharacters));
	}

	/**
	 * @return string The directory where this file resides
	 */
	private function getDir(): string {
		return $this->baseDir.'/'.$this->id[0].$this->id[1].$this->id[2];
	}

	/**
	 * @return string The file name
	 */
	private function getName(): string {
		return $this->id.'.'.$this->name;
	}

	/**
	 * @return string The local path of the file to be accessed by the code
	 */
	public function getPath(): string {
		return $this->getDir().'/'.$this->id[0].$this->id[1].$this->id[2].'/'.$this->getName();
	}

	/**
	 * @return string The URL where the file can be accessed by an HTTP client
	 */
	public function getUrl(): string {
		return $this->urlBase.'/'.$this->name;
	}

	/**
	 * @return bool Whether the file exists
	 */
	public function isExists(): bool {
		return file_exists($this->getPath());
	}

	/**
	 * @return int The size of the file on disk
	 */
	public function getSize(): int {
		return filesize($this->getPath());
	}
}
