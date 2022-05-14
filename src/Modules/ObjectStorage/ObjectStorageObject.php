<?php

namespace Cherrycake\Modules\ObjectStorage;

abstract class ObjectStorageObject {
	protected $providerName;
	protected $remoteKey;
	protected $additionalData;

	function __construct($p) {
		$this->providerName = $p["providerName"];
		$this->remoteKey = $p["remoteKey"];
		$this->additionalData = $p["additionalData"];
	}

	/**
	 * @return string The public URL of this object
	 */
	abstract public function getUrl();

	/**
	 * Deletes this object
	 */
	abstract public function delete();

	/**
	 * @return boolean Whether this object exists in the storage
	 */
	abstract public function exists();

	public function getAdditionalData($key) {
		return $this->additionalData[$key];
	}

	/**
	 * @return string A serialized version of this object
	 */
	public function serialize() {
		return serialize($this);
	}
}
