<?php

namespace Cherrycake\Modules\ObjectStorage;

use Exception;
use Cherrycake\Classes\Engine;

/**
 * A class that represents a file stored in an object storage service
 */
abstract class ObjectStorageObject {
	/**
	 * @param string $providerName The object storage provider name
	 * @param string $id The unique identifier for this object in the object storage
	 */
	function __construct(
		protected string $providerName,
		protected string $id,
	) {}

	public function getUrl(): string {
		return
			Engine::e()->ObjectStorage->getProvider($this->providerName)->getPublicEndpointUrl().
			'/'.
			$this->id;
	}

	public function delete(): bool {
		try {
			return Engine::e()->ObjectStorage->getProvider($this->providerName)->delete(id: $this->id);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}

	public function isExists(): bool {
		try {
			return Engine::e()->ObjectStorage->getProvider($this->providerName)->isExists(id: $this->id);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}

	public function getSize(): int {
		try {
			return Engine::e()->ObjectStorage->getProvider($this->providerName)->getSize(id: $this->id);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}
}
