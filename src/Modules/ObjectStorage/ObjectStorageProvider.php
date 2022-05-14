<?php

namespace Cherrycake\Modules\ObjectStorage;

use Exception;

abstract class ObjectStorageProvider {
	protected $name;
	protected $config;

	private $isConnected = false;

	function __construct($name, $config) {
		$this->name = $name;
		$this->config = $config;
	}

	/**
	 * Sets up the connection to the object storage provider and prepares any needed objects
	 * Might be called more than once in a query, so it should avoid connecting repeatedly
	 * @return boolean Whether the connection could be made
	 */
	abstract protected function connect();

	/**
	 * Puts an object in the storage
	 * @param array p Hash array with the keys:
	 * * originFile: The path to the origin file
	 * * destinationFileName: The name of the file on the storage
	 * * additionalData: An optional hash array with additional key-data pairs
	 * @return ObjectStorageObject
	 */
	abstract public function put($p);

	/**
	 * Deletes an object from the storage
	 * @param array $p Parameters to identify the object to delete
	 */
	abstract public function delete($p);

	/**
	 * @param array $p Parameters to identify the object to delete
	 * @return boolean Whether an object exists in the storage
	 */
	abstract public function exists($p);

	function requireConnection() {
		if ($this->isConnected)
			return true;
		if (!$this->connect())
			throw new Exception("Cannot connect to object storage provider {$this->name}");
	}

	protected function buildRemoteKey($fileName) {
		return
			($this->config["isPrependRandomKey"] ? $this->randomString($this->config["remoteKeyNumberOfRandomCharacters"] ?: null)."." : "").
			($this->config["className"] ? $this->config["className"]."." : "").
			$fileName;
	}

	private function randomString($characters = null) {
		if (!$characters)
			$characters = 32;
		$rnd = '';
		for ($i=0; $i < $characters; $i ++) {
			do {
				$byte = openssl_random_pseudo_bytes(1);
				$asc = chr(base_convert(substr(bin2hex($byte),0,2),16,10));
			} while (!ctype_alnum($asc));
			$rnd .= $asc;
		}
		return $rnd;
	}

	public function getPublicEndpoint($p = false) {
		return str_replace(
			[
				"{bucket}",
				"{region}"
			],
			[
				$p["bucket"] ?: $this->config["bucket"],
				$p["region"] ?: $this->config["region"]
			],
			$this->config["publicEndPoint"]
		);
	}

	/**
	 * @parameter array $p
	 * @return array A hash array of automatically build additional data based on the type of file being stored. For example, for known image formats, width, height and average color are added
	 */
	protected function buildAdditionalData($p) {
		$r = $p["additionalData"] ?? [];
		switch (mime_content_type($p["originFile"])) {
			case "image/gif":
			case "image/jpeg":
			case "image/png":
				$r = array_replace($r, $this->getImageAdditionalData($p["originFile"]) ?: []);
				break;
		}
		return $r;
	}

	function getImageAdditionalData($fileName) {
		if (!$imageSizeData = getimagesize($fileName))
			return null;
		switch ($imageSizeData[2]) {
			case IMAGETYPE_GIF:
				$srcImage = imageCreateFromGif($fileName);
				break;
			case IMAGETYPE_PNG:
				$srcImage = imageCreateFromPng($fileName);
				break;
			case IMAGETYPE_JPEG:
				$srcImage = imagecreateFromJpeg($fileName);
				break;
		}

		$tmpImage = ImageCreateTrueColor(1, 1);
		ImageCopyResampled($tmpImage, $srcImage, 0, 0, 0, 0, 1, 1, $imageSizeData[0], $imageSizeData[1]);
		$averageColorDec = ImageColorAt($tmpImage, 0, 0);
		$averageColorHex = substr("000000".dechex($averageColorDec), -6);
		return [
			"width" => $imageSizeData[0],
			"height" => $imageSizeData[1],
			"averageColorHex" => $averageColorHex
		];
	}
}
