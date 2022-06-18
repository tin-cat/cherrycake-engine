<?php

namespace Cherrycake\Modules\ObjectStorage;

abstract class ObjectStorageObject {
	/**
	 * @param string $providerName The object storage provider name
	 * @param string $id The unique identifier for this object in the object storage
	 */
	function __construct(
		protected string $providerName,
		protected string $id,
	) {}

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
