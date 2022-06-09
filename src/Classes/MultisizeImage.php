<?php

namespace Cherrycake\Classes;

/**
 * Class that represents an image that's available in multiple sizes
 */
abstract class MultisizeImage {
	/**
	 * The sizes on which the image is available, as an array like:
	 * ```php
	 * "thumbnail" => [
 	 *		"imageResizeMethod" => "maximumWidthOrHeight",
 	 *		"width" => 100,
 	 *		"height" => 100,
 	 *		"imageFormat" => "jpg",
 	 *		"isProgressive" => true,
 	 *		"jpgCompression" => 75,
 	 *		"isHd" => true
 	 * ],
 	 * "small" => []
 	 *		"imageResizeMethod" => "maximumWidthOrHeight",
 	 *		"width" => 800,
 	 *		"height" => 800,
 	 *		"imageFormat" => "jpg",
 	 *		"isProgressive" => true,
 	 *		"jpgCompression" => 90,
 	 *		"isHd" => true
 	 * ]
	 * ```
	 */
	protected $sizes;

	/**
	 * An array of Image objects corresponding to each of the sizes available
	 */
	protected $images;
}
