<?php

namespace Cherrycake\Classes;

/**
 * Class that represents an image that's available in multiple sizes
 */
abstract class MultisizeImage {
	/**
	 * The sizes on which the image is available, as an array of ImageResizeAlgorithm objects
	 */
	private array $sizes;

	/**
	 * An array of Image objects corresponding to each of the sizes available
	 */
	protected $images;

	/**
	 * Creates a multisize image from the given local image.
	 * @param string $name The file name
	 * @param string $dir The local directory where the source image file resides
	 * @param string $originalName The original file name, if it's different than $name
	 * @return MultisizeImage The created multisize image
	 */
	static public function createFromFile(
		string $name,
		string $dir,
		?string $originalName = null,
	): MultisizeImage {
		if (!$originalName)
			$originalName = $name;
		$className = get_called_class();
		return new $className;
	}
}
