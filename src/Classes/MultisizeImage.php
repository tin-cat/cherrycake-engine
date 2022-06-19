<?php

namespace Cherrycake\Classes;

/**
 * Class that represents an image that's available in multiple sizes
 */
abstract class MultisizeImage {
	/**
	 * var string $idBasedImageClassName The image class name to use, normally an App-level class that extends the core IdBasedImage class
	 */
	static protected string $idBasedImageClassName;

	/**
	 * var array $sizes The sizes on which the image is available, as an array of ImageResizeAlgorithm objects
	 */
	protected array $sizes;

	/**
	 * An array of Image objects corresponding to each of the sizes available
	 */
	protected $images;

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

			$image = new static::$idBasedImageClassName(
				originalName: $originalName,
			);

			$image->createBaseDir();

			$imageResizeAlgorithm->resize(
				sourceFilePath: $sourceImageFilePath,
				destinationFilePath: $image->getPath(),
			);

			$image->loadMetadata();

			$this->images[$sizeName] = $image;
		}
	}

	/**
	 * Returns the Image for the specified size for this MultisizeImage, null if the image doesn't exist
	 * @return Image The Image, an object of the class this::idBasedImageClassName
	 */
	public function getSizeImage(string $sizeName) {
		if (!isset($this->images[$sizeName]))
			return null;
		return $this->images[$sizeName];
	}

	/**
	 * Returnsa all Image objects contained in this multisize image where each key is the size name and each value is an Image object.
	 * If a size Image is not set, it won't be set on the returned array either.
	 * @return array An array of Image objects
	 */
	public function getImages(): array {
		return array_filter(array_map(function($sizeName) {
			return $this->getSizeImage($sizeName);
		}, array_keys($this->sizes)));
	}

	/**
	 * Deletes all the files for this MultisizeImage.
	 * Tries to delete all images even if some image fails deletion.
	 * @return bool Whether all the images for this multisize image were deleted succesfuly. If there weren't any images on the multisize image, also returns true.
	 */
	public function delete(): bool {
		if (!$images = $this->getImages())
			return true;
		$isAllImagesDeleted = true;
		foreach ($images as $image) {
			if (!$image->delete())
				$isAllImagesDeleted = false;
		}
		return $isAllImagesDeleted;
	}
}
