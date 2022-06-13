<?php

namespace Cherrycake\Classes;

use Cherrycake\Classes\File;

/**
 * Class that represents an image that's available in multiple sizes
 */
abstract class MultisizeImage {
	/**
	 * var string $baseDir The base directory where files of this class reside locally, without a trailing slash. For example: '/var/www/web/public/files'
	 */
	static protected string $baseDir;
	/**
	 * var string $baseUrl The base URL where files of this class can be loaded by an HTTP client, without a trailing slash. For example: '/files'
	 */
	static protected string $urlBase;

	/**
	 * The sizes on which the image is available, as an array of ImageResizeAlgorithm objects
	 */
	protected array $sizes;

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
		$multisizeImage = new $className;

		$image = $className::getImageObject();

		echo $image->getPath();

		return $multisizeImage;
	}

	/**
	 * @return Image An Image object to work with images for this MultisizeImage
	 */
	static public function getImageObject(): Image {
		return new Image(
			originalName: '',
			baseDir: static::$baseDir,
			urlBase: static::$urlBase,
		);
	}
}
