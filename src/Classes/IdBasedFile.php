<?php

namespace Cherrycake\Classes;

use Exception;
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
	 * var string $originalName The original name of the file, including extension
	 */
	protected string $originalName;

	/**
	 * var string $id The unique identifier of the file. If not passed, a new one is automatically generated
	 */
	protected string $id;

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return [
			'originalName',
			'id',
		];
	}

	/**
	 * @param string $filePath The complete path to the origin file. If not passed, no file is stored on disk.
	 * @param string $originalName The original file name, if it's different than $name
	 * @param string $id The unique identifier for this file. If left to null, a random one is generated
	 */
	function __construct(
		?string $filePath = null,
		?string $originalName = null,
		?string $id = null,
	) {
		if (!$id)
			$id = $this->buildUniqueFileIdentifier();

		if ($filePath) {
			if (!$originalName)
				$originalName = basename($filePath);

			$className = get_called_class();
			$idBasedFile = new $className;
			if (!$idBasedFile->copyFromLocalFile(
				filePath: $filePath,
			))
				throw new Exception('Could not create IdBasedFile from specified origin file '.$filePath);
		}

		$this->id = $id;
		$this->originalName = $originalName;
	}

	/**
	 * Creates the file on disk for this File object from the given local file.
	 * @param string $filePath The complete path to the origin file
	 * @param string $sourceName The source file name.
	 * @return bool Whether the operation completed succesfully.
	 */
	public function copyFromLocalFile(
		string $filePath
	): bool {
		$this->createBaseDir();
		if (!copy(
			from: $filePath,
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
	public function getDir(): string {
		return static::$baseDir.'/'.$this->id[0].$this->id[1].$this->id[2];
	}

	/**
	 * @return string The file name
	 */
	public function getName(): string {
		return $this->id.($this->getExtension() ? '.'.$this->getExtension() : null);
	}

	/**
	 * @return string The local path of the file
	 */
	public function getPath(): string {
		return $this->getDir().'/'.$this->getName();
	}

	/**
	 * @return string The URL where the file can be accessed via an HTTP request
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
	 * @return int The size of the file on disk in bytes
	 */
	public function getSize(): int {
		return filesize($this->getPath());
	}

	/**
	 * @return string The mime type of the file
	 */
	public function getMimeType(): string {
		return mime_content_type($this->getPath());
	}

	/**
	 * Deletes the file
	 * @return bool Whether the file was deleted succesfully
	 */
	public function delete(): bool {
		return unlink($this->getPath());
	}
}
