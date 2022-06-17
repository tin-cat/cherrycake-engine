<?php

namespace Cherrycake\Modules\ObjectStorage;

use Cherrycake\Modules\ObjectStorage\ObjectStorageObject;

abstract class ObjectStorageProvider {
	/**
	 * @var bool $isConnected Whether the connection to the object storage is stablished
	 */
	private bool $isConnected = false;

	function __construct(
		/**
		 * @param string $providerName The name of the provider
		 */
		protected string $providerName,
		/**
		 * @param array $config An array of configuration options for this storage provider
		 */
		protected array $config,
	) {}

	/**
	 * Sets up the connection to the object storage provider and prepares any needed objects
	 * Might be called more than once in a query, so it should avoid connecting repeatedly
	 * @return boolean Whether the connection could be made
	 * @throws ObjectStorageException
	 */
	abstract protected function connect(): bool;

	/**
	 * Puts an file in the object storage
	 * @param string $originFilePath The origin file local path
	 * @param string $id The object id on the object storage
	 * @return bool Whether the object was stored sucessfully
	 * @throws ObjectStorageException
	 */
	abstract public function put(
		string $originFilePath,
		string $id,
	): bool;

	/**
	 * Gets an object from the object storage
	 * @param string $id The object id on the object storage
	 * @return ObjectStorageObject The ObjectStorageObject object in the
	 * @throws ObjectStorageException
	 */
	abstract public function get(
		string $id
	): ObjectStorageObject;

	/**
	 * Deletes an object from the storage
	 * @param string $id The object id on the object storage
	 * @return bool Whether the object was deleted succesfully
	 * @throws ObjectStorageException
	 */
	abstract public function delete(
		string $id,
	): bool;

	/**
	 * @param string $id The object id on the object storage
	 * @return boolean Whether an object exists in the storage
	 * @throws ObjectStorageException
	 */
	abstract public function exists(
		string $id,
	): bool;

	/**
	 * @return string The public endpoint URL
	 */
	abstract function getPublicEndpointUrl(): string;

	/**
	 * Connects to the object storage provider if not connected
	 * @return bool Whether the connection was succesful
	 * @throws ObjectStorageException
	 */
	final function requireConnection(): bool {
		if ($this->isConnected)
			return true;
		if (!$this->connect())
			throw new ObjectStorageException("Cannot connect to object storage provider {$this->name}");
		return true;
	}
}
