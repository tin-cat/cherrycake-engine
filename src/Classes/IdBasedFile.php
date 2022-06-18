<?php

namespace Cherrycake\Classes;

use Ramsey\Uuid\Uuid;

/**
 * A class that represents a file stored in disk in a controlled path and name structure, based on an automatically generated id.
 */
abstract class IdBasedFile {
	/**
	 * var string $baseDir The base directory where files of this class reside locally, without a trailing slash. For example: '/var/www/web/public/files'
	 */
	static protected string $baseDir;
	/**
	 * var string $baseUrl The base URL where files of this class can be loaded by an HTTP client, without a trailing slash. For example: '/files'
	 */
	static protected string $urlBase;

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return [
			'originalName',
			'id',
		];
	}

	public function __construct(
		/**
		 * var string $originalName The original name of the file, including extension
		 */
		protected string $originalName,
		/**
		 * var string $id The unique identifier of the file. If not passed, a new one is automatically generated
		 */
		protected ?string $id = null,
	) {
		if (!$id)
			$this->id = $this->buildUniqueFileIdentifier();
	}

	/**
	 * Creates the file on disk for this File object from the given local file.
	 * @param string $sourceDir The directory where the source file resides, without trailing slash.
	 * @param string $sourceName The source file name.
	 * @return bool Whether the operation completed succesfully.
	 */
	public function copyFromLocalFile(
		string $sourceDir,
		string $sourceName,
	) {
		$this->createBaseDir();
		if (!copy(
			from: $sourceDir.'/'.$sourceName,
			to: $this->getPath()
		))
			return false;
	}

	/**
	 * Creates the base dir if it doesn't exists
	 */
	public function createBaseDir() {
		if (!file_exists($this->getDir().'/')) {
			mkdir(
				directory: $this->getDir(),
				permissions: 0777,
				recursive: true,
			);
		}
	}

	/**
	 * @return string A random unique identifier to identify files
	 */
	private function buildUniqueFileIdentifier(): string {
		$uuid = Uuid::uuid4();
		return $uuid->toString();
	}

	/**
	 * @return string The file extension, null if the file has no extension
	 */
	private function getExtension(): ?string {
		return pathinfo($this->originalName, PATHINFO_EXTENSION) ?? null;
	}

	/**
	 * @return string The directory where this file resides
	 */
	private function getDir(): string {
		return static::$baseDir.'/'.$this->id[0].$this->id[1].$this->id[2];
	}

	/**
	 * @return string The file name
	 */
	protected function getName(): string {
		return $this->id.($this->getExtension() ? '.'.$this->getExtension() : null);
	}

	/**
	 * @return string The local path of the file to be accessed by the code
	 */
	public function getPath(): string {
		return $this->getDir().'/'.$this->getName();
	}

	/**
	 * @return string The URL where the file can be accessed by an HTTP client
	 */
	public function getUrl(): string {
		return static::$urlBase.'/'.$this->id[0].$this->id[1].$this->id[2].'/'.$this->getName();
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

	/**
	 * Deletes the file
	 * @return bool Whether the file was deleted succesfully
	 */
	public function delete(): bool {
		return unlink($this->getPath());
	}
}
