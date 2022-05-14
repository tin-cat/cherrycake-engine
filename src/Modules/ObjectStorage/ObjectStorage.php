<?php

namespace Cherrycake\Modules\ObjectStorage;

class ObjectStorage extends \Cherrycake\Classes\Module {
	const SYSTEM_S3 = 0;

	private static $instance;

	private $providersConfig;
	private $providers;

	private $systemClassNames = [
		self::SYSTEM_S3 => "ObjectStorageProviderS3"
	];

	public function test(): string {
		return $this->getConfig('test');
	}

	public static function getProvider($providerName) {
		if (!self::$instance instanceof self)
			self::$instance = new self();

		if (isset(self::$instance->providers[$providerName]))
			return self::$instance->providers[$providerName];

		if (!isset(self::$instance->providersConfig[$providerName]))
			throw new Exception("Object storage provider $providerName not found");

		if (!isset(self::$instance->systemClassNames[self::$instance->providersConfig[$providerName]["system"]]))
			throw new Exception("Unknown object storage system specified for $providerName");

		return self::$instance->providers[$providerName] = new self::$instance->systemClassNames[self::$instance->providersConfig[$providerName]["system"]]($providerName, self::$instance->providersConfig[$providerName]);
	}

	public static function serializeObjectStorageObject($objectStorageObject) {
		return serialize($objectStorageObject);
	}

	public static function unserializeObjectStorageObject($serializedObjectStorageObject) {
		return unserialize($serializedObjectStorageObject);
	}

	public static function serializeObjectStorageObjects($objectStorageObjects) {
		return serialize($objectStorageObjects);
	}

	public static function unserializeObjectStorageObjects($serializedObjectStorageObjects) {
		return unserialize($serializedObjectStorageObjects);
	}

	/**
	 * An array of classes that implement ObjectStorageObject
	 */
	public function getClassNames() {
		foreach ($this->providersConfig as $providerConfig) {
			if (isset($providerConfig["className"]))
				$r[] = $providerConfig["className"];
		}
		return $r;
	}
}
