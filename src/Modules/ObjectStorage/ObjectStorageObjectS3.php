<?php

namespace Cherrycake\Modules\ObjectStorage;

use Exception;
use Cherrycake\Classes\Engine;

class ObjectStorageObjectS3 extends ObjectStorageObject {
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

	public function exists(): bool {
		try {
			return Engine::e()->ObjectStorage->getProvider($this->providerName)->exists(id: $this->id);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}
}
