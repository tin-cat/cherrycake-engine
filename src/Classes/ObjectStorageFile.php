<?php

namespace Cherrycake\Classes;

/**
 * An abstract class to be extended that represents a file that is first stored locally in a controlled path and name structure, and then migrated to an object storage provider.
 * The name of the file is based on an automatically generated id.
 */
abstract class ObjectStorageFile extends File {
	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return [
			'originalName',
			'baseDir',
			'urlBase',
			'id'
		];
	}

	public function __construct(
		/**
		 * var string $originalName The original name of the file, including extension
		 */
		protected string $originalName,
		/**
		 * var string $baseDir The base directory where files of this class reside locally, without a trailing slash. For example: '/var/www/web/public/files'
		 */
		protected string $baseDir,
		/**
		 * var string $baseUrl The base URL where files of this class can be loaded by an HTTP client, without a trailing slash. For example: '/files'
		 */
		protected string $urlBase,
		/**
		 * var string $id The unique identifier of the file. If not passed, a new one is automatically generated
		 */
		protected ?string $id = null,
	) {
		if (!$id)
			$this->id = $this->buildUniqueFileIdentifier();
	}
}
