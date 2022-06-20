<?php

namespace Cherrycake\Classes;

use Cherrycake\Modules\ObjectStorage\ObjectStorageObject;
use Cherrycake\Modules\ObjectStorage\ObjectStorageException;

/**
 * An abstract class to be extended that represents a file that is first stored locally in a controlled path and name structure just like with a regular IdBasedFile object, but has the additional ability to be migrated to an object storage provider
 */
class ObjectStorageIdBasedFile {
	/**
	 * @var string $providerName The object storage provider name
	 */
	protected string $providerName;

	function __construct(
		/**
		 * param IdBasedFile $idBasedFile When this file has not yet been put in object storage, the IdBasedFile object. Null if this file has been put in object storage.
		 */
		protected ?IdBasedFile $idBasedFile = null,
		/**
		 * @param ObjectStorageObject When this file is in object storage, the ObjectStorageObject object. Null if this file is not in object storage.
		 */
		protected ?ObjectStorageObject $objectStorageObject = null,
		/**
		 * @param string $providerName The object storage provider name to use, if it's different than the statically declared providerName
		 */
		?string $providerName = null,
	) {
		if ($providerName)
			$this->providerName = $providerName;
	}

	/**
	 * @return bool Whether this file is stored locally
	 */
	public function isLocal(): bool {
		return !is_null($this->idBasedFile);
	}

	/**
	 * @return bool Whether this file is stored in the object storage
	 */
	public function isInObjectStorage(): bool {
		return !is_null($this->objectStorageObject);
	}

	/**
	 * Puts this file in object storage and deletes the local file
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function moveToObjectStorage(): bool {
		if (!$this->putInObjectStorage())
			return false;
		return $this->deleteLocally();
	}

	/**
	 * Puts this file in object storage, keeps the local file
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function copyToObjectStorage(): bool {
		return $this->putInObjectStorage();
	}

	/**
	 * Puts this file in object storage
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	private function putInObjectStorage(): bool {
		if (!$this->isLocal())
			throw new ObjectStorageException('Can\'t put the file in object storage because it\'s not stored locally');

		if ($this->isInObjectStorage())
			throw new ObjectStorageException('Can\'t put file in object storage because it already is');

		if (!Engine::e()->ObjectStorage->getProvider($this->providerName)->put(
			originFilePath: $this->idBasedFile->getPath(),
			id: $this->idBasedFile->getName()
		))
			return false;

		$this->objectStorageObject = Engine::e()->ObjectStorage->getProvider($this->providerName)->get(
			id: $this->idBasedFile->getName()
		);

		return true;
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
	 * Deletes the file both from local and in object storage
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function delete(): bool {
		if (!$this->isLocal() && !$this->isInObjectStorage())
			throw new ObjectStorageException('Can\'t delete the file because it wasn\'t stored either locally nor in object storage');

		if ($this->isInObjectStorage() && !$this->deleteInObjectStorage())
			return false;

		if ($this->isLocal() && !$this->deleteLocally())
			return false;

		return true;
	}

	/**
	 * Deletes the local file
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function deleteLocally() {
		if (!$this->isLocal())
			throw new ObjectStorageException('Can\'t delete local file because it\'s not stored locally');

		if (!$this->idBasedFile->delete())
			return false;
		$this->idBasedFile = null;
		return true;
	}

	/**
	 * Deletes the file in object storage
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function deleteInObjectStorage() {
		if (!$this->isInObjectStorage())
			throw new ObjectStorageException('Can\'t delete local file because it\'s not stored in object storage');

		if (!$this->objectStorageObject->delete())
			return false;

		$this->objectStorageObject = null;
		return true;
	}

}
