<?php

namespace Cherrycake\Classes;

/**
 * A class that represents a file that resides locally
 */
class LocalFile {
	public function __construct(
		/**
		 * var $name string The name of the file, including extension
		 */
		public string $name,
		/**
		 * var $dir string The directory where the file resides locally, without a trailing slash. Null if the file is remote.
		 */
		public string $dir,
		/**
		 * var $originalName string The original name of the file if it was uploaded. Null if the file was not uploaded.
		 */
		public ?string $originalName = null,
		/**
		 * var @isUploaded bool Whether the file was uploaded
		 */
		public bool $isUploaded = false,
	) {}

	static function buildFromUploadedFile($file) {
		$className = get_called_class();
		return new $className(
			name: basename($file['tmp_name']),
			dir: dirname($file['tmp_name']),
			originalName: $file['name'],
			isUploaded: true,
		);
	}

	/**
	 * @return string The local path of the file to be accessed by the code
	 */
	public function getPath(): string {
		return $this->dir.'/'.$this->name;
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
