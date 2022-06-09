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
		 * var $nameUrl The name of the file that an HTTP client can use to load it. Null if the file was uploaded and is not public on the network.
		 */
		private ?string $urlName = null,
		/**
		 * var $baseUrl string The base URL where the file can be loaded by an HTTP client, without a trailing slash. Null if the file was uploaded and is not public on the network.
		 */
		private ?string $urlBase = null,
		/**
		 * var $originalName string The original name of the file if it was uploaded. Null if the file was not uploaded.
		 */
		private ?string $originalName = null,
	) {}

	static function buildFromUploadedFile($file) {
		return new File(
			name: basename($file['tmp_name']),
			dir: dirname($file['tmp_name']),
			originalName: $file['name']
		);
	}

	/**
	 * @return string The name of the file
	 */
	function getName(): string {
		return $this->name;
	}

	/**
	 * @return string The original name of the file. If the file was uploaded, its original name. If not, the name of the file on disk.
	 */
	function getOriginalName(): string {
		return $this->originalName ?: $this->getName();
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
