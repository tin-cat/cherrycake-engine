<?php

namespace Cherrycake\Classes;

use Cherrycake\Modules\ObjectStorage\ObjectStorageObject;
use Cherrycake\Modules\ObjectStorage\ObjectStorageException;

/**
 * An abstract class to be extended that represents a file that is first stored locally in a controlled path and name structure just like with a regular IdBasedFile object, but has the additional ability to be migrated to an object storage provider
 */
abstract class ObjectStorageIdBasedFile {
	/**
	 * @var string $providerName The object storage provider name
	 */
	static protected string $providerName;

	/**
	 * var IdBasedFile $idBasedFile When this file has not yet been put in object storage, the IdBasedFile object. Null if this file has been put in object storage.
	 */
	protected ?IdBasedFile $idBasedFile = null;

	/**
	 * @var ObjectStorageObject When this file is in object storage, the ObjectStorageObject object. Null if this file is not in object storage.
	 */
	protected ?ObjectStorageObject $objectStorageObject = null;

	/**
	 * Builds an ObjectStorageIdBasedFile object based on the given IdBasedFile
	 * @param IdBasedFile $idBasedFile
	 * @return ObjectStorageIdBasedFile
	 */
	static public function build(
		IdBasedFile $idBasedFile,
	): ObjectStorageIdBasedFile {
		$className = get_called_class();
		$objectStorageIdBasedFile = new $className;
		return $objectStorageIdBasedFile;
	}

	/**
	 * @return bool Whether this file has been stored in object storage
	 */
	public function isInObjectStorage(): bool {
		return !is_null($this->objectStorageObject);
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
		if ($this->isInObjectStorage())
			throw new ObjectStorageException('Cannot put file in object storage because it already is');

		if (!$this->objectStorageObject = Engine::e()->ObjectStorage->getProvider(self::$providerName)->put(
			originFilePath: $this->idBasedFile->getPath(),
			id: $this->idBasedFile->getName()
		))
			return false;

		if ($isDeleteLocally)
			$this->idBasedFile->delete();

		return true;
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
		return $this->idBasedFile->copyFromLocalFile(
			sourceDir: $sourceDir,
			sourceName: $sourceName,
		);
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function getUrl(): string {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->getUrl();
		else
			return $this->idBasedFile->getUrl();
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function isExists(): bool {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->isExists();
		else
			return $this->idBasedFile->isExists();
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function getSize(): int {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->getSize();
		else
			return $this->idBasedFile->getSize();
	}

	/**
	 * @throws ObjectStorageException
	 */
	public function delete(): bool {
		if ($this->isInObjectStorage())
			return $this->objectStorageObject->delete();
		else
			return $this->idBasedFile->delete();
	}
}
