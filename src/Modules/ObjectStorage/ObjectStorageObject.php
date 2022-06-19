<?php

namespace Cherrycake\Modules\ObjectStorage;

/**
 * A class that represents a file stored in an object storage service
 */
abstract class ObjectStorageObject {
	/**
	 * @var string $providerName The object storage provider name
	 */
	static string $providerName;

	/**
	 * @param string $providerName The object storage provider name
	 * @param string $id The unique identifier for this object in the object storage
	 */
	function __construct(
		protected string $id,
	) {}

	/**
	 * Puts this file in object storage
	 * @param bool $isDeleteLocally Whether to delete the file locally if the file is sucessfully put in object storage
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function put() {
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
	}

	/**
	 * @return string The public URL of this object
	 */
	abstract public function getUrl(): string;

	/**
	 * Deletes this object
	 * @return bool Whether this object was deleted succesfully
	 * @throws ObjectStorageException
	 */
	abstract public function delete(): bool;

	/**
	 * @return bool Whether this object exists on the object storage
	 * @throws ObjectStorageException
	 */
	abstract public function isExists(): bool;

	/**
	 * @return int The size of the file on disk in bytes
	 * @throws ObjectStorageException
	 */
	abstract public function getSize(): int;
}
