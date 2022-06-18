<?php

namespace Cherrycake\Classes;

use Cherrycake\Modules\ObjectStorage\ObjectStorageObject;
use Cherrycake\Modules\ObjectStorage\ObjectStorageException;

/**
 * An abstract class to be extended that represents a file that is first stored locally in a controlled path and name structure just like with a regular IdBasedFile object, but has the additional ability to be migrated to an object storage provider
 */
abstract class ObjectStorageIdBasedFile extends IdBasedFile {
	/**
	 * var string $objectStorageProviderName The name of the object storage provider
	 */
	static protected string $objectStorageProviderName;

	/**
	 * @var ObjectStorageObject When this file is in object storage, the ObjectStorageObject object. Null if this file is not in object storage.
	 */
	protected ?ObjectStorageObject $objectStorageObject = null;

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return array_merge(parent::__sleep(), [
			'objectStorageObject',
		]);
	}

	/**
	 * @return bool Whether this file has been stored in object storage
	 */
	public function isInObjectStorage(): bool {
		return $this->objectStorageProviderName ? true : false;
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

			$this->objectStorageObject = Engine::e()->ObjectStorage->getProvider(static::$objectStorageProviderName)->get(
				id: $this->getName()
			);

			return true;
		}
		return false;
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function copyFromLocalFile(
		string $sourceDir,
		string $sourceName,
	): bool {
		if ($this->isInObjectStorage())
			throw new ObjectStorageException('Cannot copy from local file because this file is already in object storage');
		return parent::copyFromLocalFile(
			sourceDir: $sourceDir,
			sourceName: $sourceName,
		);
	}

	/**
	 * @throws ObjectStorageException
	 */
	protected function getDir(): string {
		if ($this->isInObjectStorage())
			throw new ObjectStorageException('Cannot get file directory because this file is already in object storage');
		return parent::getDir();
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function getPath(): string {
		if ($this->isInObjectStorage())
			throw new ObjectStorageException('Cannot get file path because this file is already in object storage');
		return parent::getPath();
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function getUrl(): string {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->getUrl();
		return parent::getUrl();
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function isExists(): bool {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->isExists();
		return parent::isExists();
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function getSize(): int {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->getSize();
		return filesize($this->getSize());
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function delete(): bool {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->delete();
		return unlink($this->delete());
	}
}
