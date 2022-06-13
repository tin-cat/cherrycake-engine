<?php

namespace Cherrycake\Classes;

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
	 * Returns an Image object to work with this MultisizeImage
	 * @param string $originalName The original name of the file, including extension
	 * @return Image An Image object to work with images for this MultisizeImage
	 */
	static public function getImageObject(
		?string $originalName,
	): Image {
		return new Image(
			originalName: $originalName,
			baseDir: static::$baseDir,
			urlBase: static::$urlBase,
		);
	}

	/**
	 * Creates a MultisizeImage object from the given local image.
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

		$multisizeImage->createSizesFromFiles(
			name: $name,
			dir: $dir,
			originalName: $originalName,
		);

		return $multisizeImage;
	}

	/**
	 * Creates all the image sizes from the given local image.
	 * @param string $name The file name
	 * @param string $dir The local directory where the source image file resides
	 * @param string $originalName The original file name, if it's different than $name
	 */
	public function createSizesFromFiles(
		string $name,
		string $dir,
		?string $originalName = null,
	) {
		// Loop through sizes
		foreach ($this->sizes as $sizeName => $imageResizeAlgorithm) {

			$image = new Image(
				originalName: $originalName,
				baseDir: static::$baseDir,
				urlBase: static::$urlBase,
			);

			$image->createBaseDir();

			$imageResizeAlgorithm->resize(
				sourceFilePath: $dir.'/'.$name,
				destinationFilePath: $image->getPath(),
			);

			$this->images[$sizeName] = $image;
		}
	}

	/**
	 * Returns the Image for the specified size for this MultisizeImage, null if the image doesn't exist
	 * @return Image The Image
	 */
	public function getSizeImage(string $sizeName) {
		return $this->sizes[$sizeName] ?? null;
	}
}
