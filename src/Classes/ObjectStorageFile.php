<?php

namespace Cherrycake\Classes;

/**
 * An abstract class to be extended that represents a file that is first stored locally in a controlled path and name structure just like with a regular File object, but has the additional ability to be migrated to an object storage provider and used from there
 */
abstract class ObjectStorageFile extends IdBasedFile {
	/**
	 * var string $objectStorageProviderName The name of the object storage provider
	 */
	static protected string $objectStorageProviderName;

	/**
	 * @var bool $isInObjectStorage Whether this file has been stored in the object storage
	 */
	protected $isInObjectStorage = false;

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return array_merge(parent::__sleep(), [
			'isInObjectStorage',
			'objectStorageProviderName',
		]);
	}

	/**
	 * Puts this file in object storage
	 * @param bool $isDeleteLocally Whether to delete the file locally if the file is sucessfully put in object storage
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function putInObjectStorage(
		bool $isDeleteLocally = false,
	): bool {
		if (Engine::e()->ObjectStorage->getProvider(static::$objectStorageProviderName)->put(
			originFilePath: $this->getPath(),
			id: $this->getName()
		)) {
			if ($isDeleteLocally)
				$this->delete();
			$this->isInObjectStorage = true;
			return true;
		}
		return false;
	}
}
