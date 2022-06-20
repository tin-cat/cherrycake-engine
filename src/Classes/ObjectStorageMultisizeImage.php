<?php

namespace Cherrycake\Classes;

class ObjectStorageMultisizeImage extends MultisizeImage {
	/**
	 * @var string $providerName The object storage provider name
	 */
	static protected string $providerName;

	/**
	 * @param string $sourceImagefilePath The file path of the source image file, from which all sizes will be created
	 * @param string $originalName The original file name, if it's different than $name
	 */
	function __construct(
		string $sourceImageFilePath,
		?string $originalName = null,
	) {
		if (!$originalName)
			$originalName = basename($sourceImageFilePath);

		// Loop through sizes
		foreach ($this->sizes as $sizeName => $imageResizeAlgorithm) {


			$idBasedImage =
				new static::$idBasedImageClassName(
					originalName: $originalName,
				);

			$idBasedImage->createBaseDir();

			$imageResizeAlgorithm->resize(
				sourceFilePath: $sourceImageFilePath,
				destinationFilePath: $idBasedImage->getPath(),
			);

			$idBasedImage->loadMetadata();

			$objectStorageIdBasedFile = new ObjectStorageIdBasedFile(
				providerName: static::$providerName,
				idBasedFile: $idBasedImage,
			);

			$this->images[$sizeName] = $objectStorageIdBasedFile;
		}
	}

	/**
	 * Puts all files in object storage and deletes the local files
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function moveToObjectStorage(): bool {
		$isSuccess = true;
		foreach ($this->getImages() as $image) {
			if (!$image->moveToObjectStorage())
				$isSuccess = false;
		}
		return $isSuccess;
	}

	/**
	 * Puts all files in object storage, keeps the local files
	 * @return bool Whether the operation completed succesfully
	 * @throws ObjectStorageException
	 */
	public function copyToObjectStorage(): bool {
		$isSuccess = true;
		foreach ($this->getImages() as $image) {
			if (!$image->copyToObjectStorage())
				$isSuccess = false;
		}
		return $isSuccess;
	}

	/**
	 * @return bool Whether all files are stored locally
	 */
	public function isLocal(): bool {
		$isLocal = true;
		foreach ($this->getImages() as $image) {
			if (!$image->isLocal())
				$isLocal = false;
		}
		return $isLocal;
	}

	/**
	 * @return bool Whether all files are stored in the object storage
	 */
	public function isInObjectStorage(): bool {
		$isInObjectStorage = true;
		foreach ($this->getImages() as $image) {
			if (!$image->isInObjectStorage())
				$isInObjectStorage = false;
		}
		return $isInObjectStorage;
	}
}
