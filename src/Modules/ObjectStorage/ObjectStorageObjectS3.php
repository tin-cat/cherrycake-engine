<?php

namespace Cherrycake\Modules\ObjectStorage;

class ObjectStorageObjectS3 extends ObjectStorageObject {
	protected $region;
	protected $bucket;

	function __construct($p) {
		parent::__construct($p);
		$this->region = $p["region"];
		$this->bucket = $p["bucket"];
	}

	public function getUrl() {
		return
			ObjectStorage::getProvider($this->providerName)->getPublicEndpoint([
				"bucket" => $this->bucket
			]).
			"/".
			$this->remoteKey;
	}

	public function delete() {
		ObjectStorage::getProvider($this->providerName)->delete([
			"bucket" => $this->bucket,
			"remoteKey" => $this->remoteKey
		]);
	}

	public function exists() {
		return ObjectStorage::getProvider($this->providerName)->exists([
			"bucket" => $this->bucket,
			"remoteKey" => $this->remoteKey
		]);
	}
}
