<?php

namespace Cherrycake\Modules\ObjectStorage;

use Exception;
use Cherrycake\Classes\Engine;

class ObjectStorageObjectS3 extends ObjectStorageObject {
	public function getUrl(): string {
		return
			Engine::e()->ObjectStorage->getProvider(static::$providerName)->getPublicEndpointUrl().
			'/'.
			$this->id;
	}

	public function delete(): bool {
		try {
			return Engine::e()->ObjectStorage->getProvider(static::$providerName)->delete(id: $this->id);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}

	public function isExists(): bool {
		try {
			return Engine::e()->ObjectStorage->getProvider(static::$providerName)->isExists(id: $this->id);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}

	public function getSize(): int {
		try {
			return Engine::e()->ObjectStorage->getProvider(static::$providerName)->getSize(id: $this->id);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}
}
